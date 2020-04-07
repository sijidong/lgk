define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/order_shop/index' + location.search,
                    add_url: 'order/order_shop/add',
                    edit_url: 'order/order_shop/edit',
                    del_url: 'order/order_shop/del',
                    multi_url: 'order/order_shop/multi',
                    table: 'order_shop',
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
                        {field: 'goods_id', title: __('Goods_id')},
                        {field: 'goods_name', title: __('Goods_name')},
                        {field: 'number', title: __('Number')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
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