define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/rule_user_level/index' + location.search,
                    add_url: 'user/rule_user_level/add',
                    edit_url: 'user/rule_user_level/edit',
                    del_url: 'user/rule_user_level/del',
                    multi_url: 'user/rule_user_level/multi',
                    table: 'rule_user_level',
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
                        {field: 'produce', title: __('Produce'), operate:'BETWEEN'},
                        {field: 'team_produce', title: __('Team_produce'), operate:'BETWEEN'},
                        {field: 'team_num', title: __('Team_num')},
                        {field: 'next_num', title: __('Next_num')},
                        {field: 'profit', title: __('Profit'), operate:'BETWEEN'},
                        {field: 'equal_profit', title: __('Equal_profit'), operate:'BETWEEN'},
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