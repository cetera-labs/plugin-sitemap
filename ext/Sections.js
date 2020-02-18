Ext.require('Plugin.sitemap.SiteTreeCheck');

Ext.define('Plugin.sitemap.Sections', {
    extend: 'Cetera.field.Panel',
    alias: 'widget.sitemap_sections',
    loadDelay: null,

    prepareValue: function () {
        var dirs_parse = [];
        var dom = Ext.dom.Query.select('.js-dirs');
        dom.forEach(function (item, i, arr) {
            if (!item.checked)
                dirs_parse.push(item.value);
        });

        this.panel.getStore().getRootNode().cascadeBy(function (child) {
            var id = child.get("id"),
                checked = child.get("checked"),
                parseElements = child.get("parseElements");

            if (parseInt(id) > 0) {
                if (checked)
                    dirs_parse.push("s-" + id);
                if (parseElements !== "N" && checked)
                    dirs_parse.push("e-" + id);
            }
        });

        var val = dirs_parse.join(",");
        this.setValue(val, true);
    },

    getPanel: function () {
        var parent = this;
        return Ext.create('Plugin.sitemap.SiteTreeCheck', {
            from: '0',
            fieldLabel: _('Разделы'),
            rowLines: true,
            autoLoad: false,
            scroll: true,
            autoScroll: false,
            height: "400px",
            listeners: {
                checkchange: function (node, check) {
                    node.cascadeBy(function (child) {
                        child.set("checked", check);
                    });
                    if (check) {
                        var parentNode = node;
                        do {
                            parentNode = parentNode.parentNode;
                            if (parentNode)
                                parentNode.set("checked", check);
                        }
                        while (parentNode);
                    }
                    this.reInitEvents(parent);
                },
                afteritemexpand: function (node, index, item, eOpts) {
                    this.reInitEvents(parent);
                },
                afteritemcollapse: function (node, index, item, eOpts) {
                    this.reInitEvents(parent);
                },
                load: function () {
                }
            }
        });
    },

    setValue: function (value, internal) {
        this.callParent(arguments);
        if (internal) return;

        if (value !== undefined) {
            var parent = this;
            if (this.panel.getStore().isLoading()) {
                parent.loadDelay = setInterval(function () {
                    if (!parent.panel.getStore().isLoading()) {
                        clearInterval(parent.loadDelay);
                        parent.panel.store.proxy.extraParams["dirs"] = value;
                        parent.panel.getStore().load({
                            callback: function () {
                                parent.panel.reInitEvents(parent);
                            },
                            scope: parent.panel
                        });
                    }
                }, 500);
            }
            else {
                parent.panel.store.proxy.extraParams["dirs"] = value;
                parent.panel.getStore().load({
                    callback: function () {
                        parent.panel.reInitEvents(parent);
                        parent.panel.getStore().getRootNode().expand();
                    },
                    scope: parent.panel
                });
                clearInterval(parent.loadDelay);
            }
        }
    },

    initComponent: function () {
        this.callParent();

        this.panel.getSelectionModel().on('selectionchange', function () {
            this.prepareValue();
        }, this);
    }

});