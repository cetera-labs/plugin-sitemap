Ext.require('Cetera.field.WidgetTemplate');
Ext.require('Plugin.sitemap.Sections');

Ext.define('Plugin.sitemap.Widget', {

    extend: 'Cetera.widget.Widget',

    initComponent: function () {
        this.formfields = [
            {
                xtype: 'widgettemplate',
                widget: 'Sitemap'
            },
            {
                name: 'sections',
                xtype: 'sitemap_sections',
                fieldLabel: _('Разделы')
            }
        ];

        this.callParent();
    },
});
