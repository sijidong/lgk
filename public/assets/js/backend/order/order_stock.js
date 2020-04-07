define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/order_stock/index' + location.search,
                    add_url: 'order/order_stock/add',
                    edit_url: 'order/order_stock/edit',
                    del_url: 'order/order_stock/del',
                    multi_url: 'order/order_stock/multi',
                    table: 'order_stock',
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
                        {field: 'order_number', title: __('Order_number')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.mobile', title: __('手机号'),operate:'LIKE'},
                        {field: 'number', title: __('Number')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'balance', title: __('余额'), operate:'BETWEEN'},
                        {field: 'pay_type', title: __('Pay_type'), searchList: {"recharge":__('Pay_type recharge'),"static":__('Pay_type static'),"dynamic":__('Pay_type dynamic')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"19":__('Status 10')},formatter: Table.api.formatter.normal},
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