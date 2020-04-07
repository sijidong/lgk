define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mining_withdraw/index' + location.search,
                    add_url: 'mining_withdraw/add',
                    edit_url: 'mining_withdraw/edit',
                    del_url: 'mining_withdraw/del',
                    multi_url: 'mining_withdraw/multi',
                    table: 'mining_withdraw',
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
                        {field: 'number', title: __('Number'), operate:'BETWEEN'},
                        {field: 'hand_fee', title: __('Hand_fee'), operate:'BETWEEN'},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'address', title: __('Address')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4')}, formatter: Table.api.formatter.status},
                        // {field: 'type', title: __('Type')},
                        {field: 'wallet.block_tx_link', title: __('账单地址'),formatter:Table.api.formatter.url},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    // hidden:function(row){
                                    //     if(row.status != "0"){
                                    //         if(row.wallet.status != '0' && row.status != "1")
                                    //         {
                                    //             return true;
                                    //         }
                                    //     }
                                    // },
                                    visible:function(row){
                                      if (row.status == "0" || (row.wallet.status == 0 && row.status == 1)) {
                                          return true;
                                      }
                                    },
                                    name: 'detail',
                                    title: __('确认'),
                                    text: __('通过'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-share',
                                    confirm: '确定通过？',
                                    url: 'mining_withdraw/update_status?type=pass',
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
                                    visible:function(row){
                                        if (row.status == "0" || (row.wallet.status == 0 && row.status == 1)) {
                                            return true;
                                        }
                                    },
                                    name: 'detail',
                                    title: __('反驳'),
                                    text: __('拒绝'),
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    icon: 'fa fa-share',
                                    confirm: '确认拒绝？',
                                    url: 'mining_withdraw/update_status?type=reject',
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
    $('.designate').click(function(){
        var arr=[];
        var tr=$('#table tr');
        for(var i=1 ;i<tr.length;i++){
            if($('#table tr').eq(i).is(".selected")) {
                // do something
                var id=$('#table tr').eq(i).find('td').eq(1).html();
                arr.push(id);
            }
        }
        window.location.href='/admin/mining_withdraw/multi_update_status?ids=' + arr;
    });
    return Controller;
});