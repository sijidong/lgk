define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mining/mining_calculation/index' + location.search,
                    add_url: 'mining/mining_calculation/add',
                    edit_url: 'mining/mining_calculation/edit',
                    del_url: 'mining/mining_calculation/del',
                    multi_url: 'mining/mining_calculation/multi',
                    table: 'mining_calculation',
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
                        {field: 'mine_user_id', title: __('Mine_user_id')},
                        {field: 'mining_number', title: __('Mining_number')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'name', title: __('Name')},
                        {field: 'calculation', title: __('Calculation'), operate:'BETWEEN'},
                        {field: 'coin_type', title: __('Coin_type')},
                        // {field: 'mining_type_id', title: __('Mining_type_id')},
                        {field: 'number', title: __('Number'), operate:'BETWEEN'},
                        // {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        // {field: 'begin_time', title: __('Begin_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'date', title: __('Date'), operate:'RANGE', addclass:'datetimerange'},
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