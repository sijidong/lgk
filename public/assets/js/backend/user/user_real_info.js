define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user_real_info/index' + location.search,
                    add_url: 'user/user_real_info/add',
                    edit_url: 'user/user_real_info/edit',
                    del_url: 'user/user_real_info/del',
                    multi_url: 'user/user_real_info/multi',
                    table: 'user_real_info',
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
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.mobile', title: __('手机号'),operate:'LIKE'},
                        {field: 'name', title: __('Name')},
                        {field: 'id_card', title: __('Id_card')},
                        {field: 'a_image', title: __('A_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'b_image', title: __('B_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'area', title: __('Area')},
                        {field: 'birthday', title: __('Birthday')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    hidden:function(row){
                                        if(row.status != '0'){
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('确认'),
                                    text: __('不通过'),
                                    classname: 'btn btn-xs btn-warning btn-magic btn-ajax',
                                    icon: 'fa fa-mail-reply',
                                    confirm: '确定？',
                                    url: 'user/user_real_info/realauth?type=reject',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                },
                                {
                                    hidden:function(row){
                                        if(row.status != '0'){
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('确认'),
                                    text: __('通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-share',
                                    confirm: '确定？',
                                    url:'user/user_real_info/realauth?type=pass',
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data, ret) {
                                        Layer.alert(ret.msg);
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