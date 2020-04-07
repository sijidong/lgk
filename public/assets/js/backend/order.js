define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
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
                        {field: 'goods_id', title: __('Goods_id')},
                        {field: 'goods_name', title: __('Goods_name')},
                        {field: 'number', title: __('Number')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'calculation', title: __('Calculation'), operate:'BETWEEN'},
                        // {field: 'duration', title: __('Duration')},
                        {field: 'pay_type', title: __('Pay_type'), searchList: {"0":__('Pay_type 0'),"1":__('Pay_type 1'),"2":__('Pay_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'manage_type', title: __('Manage_type'), searchList: {"self":__('Manage_type self'),"platform":__('Manage_type platform')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"2":__('Status 2'),"3":__('Status 3'),"10":__('Status 10')}, formatter: Table.api.formatter.status},
                        {field: 'receive_name', title: __('Receive_name')},
                        {field: 'receive_mobile', title: __('Receive_mobile')},
                        {field: 'receive_address', title: __('Receive_address')},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'delivery_time', title: __('Delivery_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange'},
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