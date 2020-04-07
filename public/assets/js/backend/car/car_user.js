define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'car/car_user/index' + location.search,
                    add_url: 'car/car_user/add',
                    edit_url: 'car/car_user/edit',
                    del_url: 'car/car_user/del',
                    multi_url: 'car/car_user/multi',
                    table: 'car_user',
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
                        {field: 'car_id', title: __('Car_id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.mobile', title: __('手机号'),operate:'LIKE'},
                        {field: 'coin_price', title: __('Coin Price')},
                        {field: 'coin1', title: __('Coin1'), operate:'BETWEEN'},
                        {field: 'coin1_balance', title: __('Coin1 Balance'), operate:'BETWEEN'},
                        {field: 'coin2', title: __('Coin2'), operate:'BETWEEN'},
                        {field: 'gas', title: __('Gas'), operate:'BETWEEN'},
                        {field: 'coin2_balance', title: __('Coin2 Balance'), operate:'BETWEEN'},
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