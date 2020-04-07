define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'statement/statement_property/index' + location.search,
                    add_url: 'statement/statement_property/add',
                    edit_url: 'statement/statement_property/edit',
                    del_url: 'statement/statement_property/del',
                    multi_url: 'statement/statement_property/multi',
                    table: 'statement_property',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'origin_recharge', title: __('Origin_recharge'), operate:'BETWEEN'},
                        {field: 'origin_dynamic', title: __('Origin_dynamic'), operate:'BETWEEN'},
                        {field: 'origin_static', title: __('Origin_static'), operate:'BETWEEN'},
                        {field: 'origin_buy', title: __('Origin_buy'), operate:'BETWEEN'},
                        {field: 'base', title: __('Base'), operate:'BETWEEN'},
                        {field: 'stock', title: __('Stock'), operate:'BETWEEN'},
                        {field: 'score', title: __('Score'), operate:'BETWEEN'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});