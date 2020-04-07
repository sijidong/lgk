define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user_level_rule/index' + location.search,
                    add_url: 'user/user_level_rule/add',
                    edit_url: 'user/user_level_rule/edit',
                    del_url: 'user/user_level_rule/del',
                    multi_url: 'user/user_level_rule/multi',
                    table: 'user_level_rule',
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
                        {field: 'name', title: __('Name')},
                        {field: 'direct_push_rate', title: __('Direct_push_rate'), operate:'BETWEEN'},
                        {field: 'equal', title: __('Equal'), operate:'BETWEEN'},
                        {field: 'equal_1', title: __('Equal_1'), operate:'BETWEEN'},
                        {field: 'next_num', title: __('Next_num')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
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