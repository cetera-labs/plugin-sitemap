Ext.require('Plugin.sitemap.ModelSiteTreeCheck');

Ext.define('Plugin.sitemap.SiteTreeCheck', {

    extend: 'Ext.tree.TreePanel',

    rootVisible: false,
    line: false,
    autoScroll: true,

    loadMask: true,
    itemID: false,

    initComponent: function () {
        if (this.url)
            var url = this.url;
        else var url = '/plugins/sitemap/scripts/data_tree.php?v=1';

        this.store = new Ext.data.TreeStore({
            model: Plugin.sitemap.ModelSiteTreeCheck,
            rootVisible: false,
            proxy: {
                type: 'ajax',
                url: url,
                actionMethods: {
                    read: 'POST'
                },
                limitParam: false,
                startParam: false,
                pageParam: false,
                extraParams: {
                    dirs: this.dirs
                }
            },
            root: {
                text: 'root',
                id: 'root',
                iconCls: 'tree-folder-visible',
                expanded: true
            }
        });

        this.columns = [{
            xtype: 'treecolumn',
            text: _('Раздел'),
            dataIndex: 'text',
            flex: 1,
            renderer: function (val, meta, rec) {
                return val;
            }
        }, {
            text: _('Элементы'),
            dataIndex: 'elements',
            width: 100
        }];

        this.callParent();

        this.getSelectionModel().on({
            beforeselect: function (sm, node) {
                if (node.get('disabled')) return false;
            }
        });

        this.listeners = {
            'load': {
                fn: function (store, records, success) {
                    this.setLoading(false);
                },
                scope: this
            },
            'beforeload': {
                fn: function (store, records, success) {
                    this.setLoading(true);
                },
                scope: this
            }
        };
    },

    afterRender: function () {
        this.callParent();
    },

    getSelectedId: function () {
        var sn = this.getSelectionModel().getLastSelected();
        if (!sn) return false;
        var a = sn.getId().split('-');
        return a[1];
    },

    reInitEvents: function (parent) {
        var _this = this;
        var dom = Ext.dom.Query.select('.js-element-hide');
        dom.forEach(function (item, i, arr) {
            var el = Ext.get(dom[i]),
                id = item.getAttribute('data-id');

            Ext.EventManager.removeAll(el);
            el.on('click', function (e) {
                var status = item.getAttribute('data-status'),
                    checked = status == "Y" ? false : true;

                var node = _this.getStore().getNodeById(id);
                if (!checked) {
                    item.innerText = _("Нет");
                    item.setAttribute("data-status", "N");
                    node.set("parseElements", "N");

                    node.cascadeBy(function (child) {
                        var id = child.get("id");
                        if (id !== null) {
                            child.set("parseElements", "N");
                            child.set("elements", "<a href='#' data-id='" + id + "' data-status='N' class='js-element-hide'>" + _("Нет") + "</a>");
                        }
                    });
                }
                else {
                    item.innerText = _("Да");
                    item.setAttribute("data-status", "Y");
                    node.set("parseElements", "Y");

                    node.cascadeBy(function (child) {
                        var id = child.get("id");
                        if (id !== null) {
                            child.set("parseElements", "Y");
                            child.set("elements", "<a href='#' data-id='" + id + "' data-status='Y' class='js-element-hide'>" + _("Да") + "</a>");
                        }
                    });
                }

                _this.reInitEvents(parent);
                e.preventDefault();
                return false;
            }, this);
        });
        if (parent !== undefined)
            parent.prepareValue();
    },
});