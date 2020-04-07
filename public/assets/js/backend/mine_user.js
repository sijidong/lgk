define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mine_user/index' + location.search,
                    add_url: 'mine_user/add',
                    edit_url: 'mine_user/edit',
                    del_url: 'mine_user/del',
                    multi_url: 'mine_user/multi',
                    table: 'mine_user',
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
                        {field: 'mining_number', title: __('Mining_number')},
                        {field: 'order_id', title: __('Order_id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'goods_id', title: __('Goods_id')},
                        {field: 'name', title: __('Name')},
                        {field: 'calculation', title: __('Calculation'), operate:'BETWEEN'},
                        // {field: 'duration', title: __('Duration')},
                        {field: 'mac', title: __('Mac')},
                        {field: 'mortgage_number', title: __('Mortgage_number'), operate:'BETWEEN'},
                        {field: 'manage_type', title: __('Manage_type'), searchList: {"self":__('Manage_type self'),"platform":__('Manage_type platform')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'bind_time', title: __('Bind_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    hidden:function(row){
                                        if(row.status != "2"){
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('确认'),
                                    text: __('确认'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-share',
                                    confirm: '确定？',
                                    url: 'mine_user/review?type=pass',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        // return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        // return false;
                                    },
                                    // visible: function (row) {
                                    //     console.log(row);
                                    //     //返回true时按钮显示,返回false隐藏
                                    //     return true;
                                    // }
                                },
                                {
                                    hidden:function(row){
                                        if(row.status != "2"){
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('反驳'),
                                    text: __('反驳'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-share',
                                    confirm: '确定？',
                                    url: 'mine_user/review?type=reject',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                        //如果需要阻止成功提示，则必须使用return false;
                                        // return false;
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        // return false;
                                    },
                                    // visible: function (row) {
                                    //     console.log(row);
                                    //     //返回true时按钮显示,返回false隐藏
                                    //     return true;
                                    // }
                                }
                            ],
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