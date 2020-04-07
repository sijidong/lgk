define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index' + location.search,
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                },
                pageList: [10, 50, 100,500,1000, 'All'],
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showColumns:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'pid', title: __('Pid')},
                        // {field: 'group_id', title: __('Group_id')},
                        // {field: 'username', title: __('Username')},
                        {field: 'nickname', title: __('Nickname')},
                        // {field: 'password', title: __('Password')},
                        // {field: 'deal_password', title: __('Deal_password')},
                        // {field: 'salt', title: __('Salt')},
                        {field: 'email', title: __('Email')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'useraddress.address', title: __('钱包地址')},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'rule_user_level_id', title: __('Rule_user_level_id'),searchList: {"1":__('初级节点'),"2":__('中级节点'),"3":__('高级节点'),"4":__('董事节点')}, formatter: Table.api.formatter.normal},
                        {field: 'produce_level',title:'VIP等级',operate:false},
                        {field: 'origin_recharge', title: __('Origin_recharge'),visible:Config.can_money,operate:false},
                        {field: 'origin_dynamic', title: __('Origin_dynamic'),visible:Config.can_money,operate:false},
                        {field: 'origin_buy', title: __('Origin_buy'),visible:Config.can_money,operate:false},
                        {field: 'base', title: __('Base'),visible:Config.can_money,operate:false},
                        {field: 'stock', title: __('Stock'),visible:Config.can_money,operate:false},
                        {field: 'origin_static', title: __('Origin_static'),visible:Config.can_money,operate:false},
                        {field: 'score', title: __('Score'),visible:Config.can_money,operate:false},
                        {field: 'coin', title: __('LGK2'),visible:Config.can_money,operate:false},
                        // {field: 'gender', title: __('Gender')},
                        // {field: 'birthday', title: __('Birthday'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'invitecode', title: __('Invitecode')},
                        // {field: 'team_num', title: __('Team_num')},
                        // {field: 'trade_center', title: __('Trade_center'), searchList: {"0":__('Trade_center 0'),"1":__('Trade_center 1')}, formatter: Table.api.formatter.normal},
                        {field: 'real_auth', title: __('Real_auth'), searchList: {"0":__('Real_auth 0'),"1":__('Real_auth 1'),"2":__('Real_auth 2')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status},

                        // {field: 'successions', title: __('Successions')},
                        // {field: 'maxsuccessions', title: __('Maxsuccessions')},
                        {field: 'prevtime', title: __('Prevtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'logintime', title: __('Logintime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'loginip', title: __('Loginip')},
                        {field: 'loginfailure', title: __('Loginfailure')},
                        {field: 'joinip', title: __('Joinip')},
                        // {field: 'jointime', title: __('Jointime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'token', title: __('Token')},

                        // {field: 'verification', title: __('Verification')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons:[{
                                name: 'detail',
                                title: __('查看'),
                                text: __('查看'),
                                classname: 'btn btn-xs btn-success btn-dialog chakan',
                                icon: 'fa fa-list',
                                url: 'user/user/detail',
                            }],
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
        // detail: function(){
        //     $("#detail").on('click', function() {
        //         $("#send-form").attr("action","user/user/detail").submit();
        //     });
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});