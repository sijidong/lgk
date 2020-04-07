define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/order_origin/index' + location.search,
                    add_url: 'order/order_origin/add',
                    edit_url: 'order/order_origin/edit',
                    del_url: 'order/order_origin/del',
                    multi_url: 'order/order_origin/multi',
                    table: 'order_origin',
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
                        {field: 'order_market_id', title: __('Order_market_id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.mobile', title: __('手机号'),operate:'LIKE'},
                        {field: 'sell_user_id', title: __('Sell_user_id')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'number', title: __('Number')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'payment', title: __('支付凭证'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'pay_type', title: __('Pay_type'), searchList: {"0":__('Pay_type 0'),"1":__('Pay_type 1'),"2":__('Pay_type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    hidden:function(row){
                                        if(row.status != '2'){
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('确认'),
                                    text: __('取消'),
                                    classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                    icon: 'fa fa-mail-reply',
                                    confirm: '确定？',
                                    url: 'order/order_origin/updateStatus?type=reject',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                },
                                {
                                    hidden:function(row){
                                        if(row.status != '2'){
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('确认'),
                                    text: __('放行'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-share',
                                    confirm: '确定？',
                                    url: 'order/order_origin/updateStatus?type=pass',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        // return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        // return false;
                                    },
                                }
                            ]
                        }
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