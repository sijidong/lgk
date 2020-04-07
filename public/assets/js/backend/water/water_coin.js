define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/water_coin/index' + location.search,
                    add_url: 'water/water_coin/add',
                    edit_url: 'water/water_coin/edit',
                    del_url: 'water/water_coin/del',
                    multi_url: 'water/water_coin/multi',
                    table: 'water_coin',
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
                        {field: 'detail_id', title: __('Detail_id')},
                        {field: 'relate_user_id', title: __('Relate_user_id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.mobile', title: __('手机号'),operate:'LIKE'},
                        {field: 'type', title: __('Type'), searchList: {"0":__('Type 0'),"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"6":__('Type 6'),"7":__('Type 7'),"11":__('Type 11'),"12":__('Type 12'),"13":__('Type 13')}, formatter: Table.api.formatter.normal},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'balance', title: __('Balance'), operate:'BETWEEN'},
                        {field: 'mark', title: __('Mark')},
                        {field: 'lmark', title: __('Lmark')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange'},
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