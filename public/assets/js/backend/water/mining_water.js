define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/mining_water/index' + location.search,
                    add_url: 'water/mining_water/add',
                    edit_url: 'water/mining_water/edit',
                    del_url: 'water/mining_water/del',
                    multi_url: 'water/mining_water/multi',
                    table: 'mining_water',
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
                        {field: 'type', title: __('Type'), searchList: {"0":__('Type 0'),"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"4":__('Type 4'),"7":__('Type 7'),"8":__('Type 8'),"9":__('Type 9'),"10":__('Type 10'),"11":__('Type 11'),"12":__('Type 12'),"20":__('Type 20'),"21":__('Type 21'),"22":__('Type 22'),"24":__('Type 24')}, formatter: Table.api.formatter.normal},
                        {field: 'coin_type', title: __('Coin_type')},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'mark', title: __('Mark')},
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