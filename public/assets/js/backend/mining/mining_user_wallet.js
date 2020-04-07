define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'mining/mining_user_wallet/index' + location.search,
                    add_url: 'mining/mining_user_wallet/add',
                    edit_url: 'mining/mining_user_wallet/edit',
                    del_url: 'mining/mining_user_wallet/del',
                    multi_url: 'mining/mining_user_wallet/multi',
                    table: 'mining_user_wallet',
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
                        {field: 'mining_type_id', title: __('Mining_type_id')},
                        {field: 'mining_type_id_str', title: __('币种'),operate:false},
                        {field: 'balance', title: __('Balance'), operate:'BETWEEN'},
                        {field: 'address', title: __('地址'), operate:false},
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