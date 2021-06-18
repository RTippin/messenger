window.ThreadBots = (function () {
    var opt = {
        lock : true,
        thread : null,
        bots_table : null,
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
        }
    },
    methods = {
        setThread : function () {
            if (ThreadManager.state()._thread) {
                opt.thread = ThreadManager.state()._thread;
                return true;
            }

            return false;
        },
        loadDataTable : function(elm, term){
            if(opt.bots_table) opt.bots_table.destroy();
            if(!elm || !elm.length) return;
            opt.bots_table = elm.DataTable({
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ "+term,
                    "lengthMenu": "Show _MENU_ "+term,
                    "infoEmpty": "Showing 0 to 0 of 0 "+term,
                    "infoFiltered": "(filtered from _MAX_ total "+term+")",
                    "emptyTable": "No "+term+" found",
                    "zeroRecords": "No matching "+term+" found"
                },
                "drawCallback": function(settings){
                    let api = new $.fn.DataTable.Api(settings), pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                    pagination.toggle(api.page.info().pages > 1);
                    LazyImages.update();
                }
            });
        },
        viewBots : function(){
            if (!methods.setThread()) return;
            let gather = () => {
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots',
                    success : function(data){
                        Messenger.alert().fillModal({
                            body : templates.view_bots(data),
                            title : opt.thread.name+' Bots'
                        });
                        methods.loadDataTable($("#view_bots_table"), 'bots')
                    },
                    fail_alert : true
                })
            };
            Messenger.alert().Modal({
                icon : 'robot',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Loading Bots...',
                pre_loader : true,
                overflow : true,
                unlock_buttons : false,
                h4 : false,
                size : 'lg',
                onReady : gather
            });
        },
        addBot : function(){
            Messenger.alert().Modal({
                icon : 'robot',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Add Bot',
                overflow : true,
                unlock_buttons : false,
                h4 : false,
                size : 'md',
                body : templates.add_bot(),
                cb_btn_txt : 'Add Bot',
                cb_btn_icon : 'robot',
                cb_btn_theme : 'success',
                onReady : function(){
                    $(".m_setting_toggle").change(function(){
                        $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
                    })
                },
                callback : methods.storeBot
            });
        },
        storeBot : function(){
            if (!methods.setThread()) return;
            let cooldown =  $("#g_s_bot_cooldown");
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots',
                data : {
                    name : $('#g_s_bot_name').val(),
                    enabled : $("#g_s_bot_enabled").is(":checked"),
                    hide_actions : $("#g_s_hide_actions").is(":checked"),
                    cooldown : cooldown.val().length ? cooldown.val() : 0,
                },
                success : methods.viewBots,
                fail_alert : true,
            });
        }
    },
    templates = {
        view_bots : function(bots){
            let add_btn = '<div class="col-12 text-center mt-3"><button onclick="ThreadBots.addBot()" type="button" class="btn btn-success">Add Bot <i class="fas fa-robot"></i></button></div>';

            return templates.bots(bots) + (opt.thread.options.manage_bots ? add_btn : '');
        },
        bots : function(bots){
            let table_top = '<div class="row">\n' +
                '    <div class="col-12">\n' +
                '        <div class="table-responsive-sm">\n' +
                '            <table id="view_bots_table" class="table table-sm table-hover">\n' +
                '                <thead>\n' +
                '                <tr>\n' +
                '                    <th>Name</th>\n' +
                '                    <th>Enabled</th>\n' +
                '                    <th>On Cooldown</th>\n' +
                '                    <th>Cooldown</th>\n' +
                '                    <th>Actions</th>\n' +
                '                    <th>Options</th>\n' +
                '                </tr>\n' +
                '                </thead>\n' +
                '                <tbody>',
                table_bot = '</tbody></table></div></div></div>',
                table_fill = '';
            let bot_fill = (bot) => {
                return '<tr id="row_'+bot.id+'">\n' +
                    '     <td>\n' +
                    '      <div class="table_links">\n' +
                    '        <div class="nowrap">\n' +
                    '          <img class="lazy rounded-circle group-image avatar-is-offline" data-src="'+bot.avatar.sm+'" />\n' +
                    '          <span class="h5"><span class="badge badge-light">'+bot.name+'</span></span>\n' +
                    '         </div>\n' +
                    '       </div>\n' +
                    '  </td>'+
                    '  <td class="h5">\n' +
                          (bot.enabled ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times-circle"></i></span>') +
                    '  </td>\n' +
                    '  <td class="h5">\n' +
                          (bot.on_cooldown ? '<span class="badge badge-danger"><i class="fas fa-hourglass-half"></i> Yes</span>' : '<span class="badge badge-success"><i class="fas fa-hourglass-start"></i> No</span>') +
                    '  </td>\n' +
                    '  <td class="h5">'+bot.cooldown+' seconds</td>\n' +
                    '  <td class="h5">'+bot.actions_count+'</td>\n' +
                    '  <td>\n' +
                    '  <div class="dropdown float-right">\n' +
                    '    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown"><i class="fas fa-cog"></i></button>\n' +
                    '    <div class="dropdown-menu dropdown-menu-right">\n' +
                    '       <a class="dropdown-item" onclick="return false;" href="#" title="View"><i class="fas fa-robot"></i> View</a>' +
                    ' </span>\n' +
                    ' </div></div></td>\n' +
                    '</tr>'
            };
            if(bots && bots.length){
                bots.forEach((bot) => { table_fill += bot_fill(bot)})
            }
            return table_top+table_fill+table_bot
        },
        add_bot : function(){
            return '<form id="new_bot_form" action="">\n' +
                '<div class="form-row mx-n2 rounded bg-light text-dark py-3 px-2 shadow-sm">\n' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <div class="input-group-text"><i class="fas fa-robot"></i></div>\n' +
                '         </div>\n' +
                '         <input autocomplete="off" minlength="2" class="form-control font-weight-bold shadow-sm" id="g_s_bot_name" placeholder="Bot Name" name="bot-name-'+Date.now()+'" required>' +
                '     </div>\n' +
                '</div>' +
                '<hr>' +
                '<div class="form-row mx-n2 rounded bg-light text-dark py-3 px-2 shadow-sm">\n' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <div class="input-group-text"><i class="fas fa-clock"></i></div>\n' +
                '         </div>\n' +
                '         <input type="number" autocomplete="off" min="0" max="900" class="form-control font-weight-bold shadow-sm" id="g_s_bot_cooldown" placeholder="Bot Cooldown" name="bot-cooldown-'+Date.now()+'" required>' +
                '     </div>\n' +
                '</div>'+
                '    <hr>\n' +
                '    <div class="form-row mx-n2 rounded bg-light text-dark pb-3 pt-2 px-3 shadow-sm">\n' +
                '        <label class="font-weight-bold h5 control-label" for="g_s_table">Bot Toggles:</label>\n' +
                '        <table id="g_s_table" class="table mb-0 table-sm table-hover">\n' +
                '            <tbody>\n' +
                '            <tr class="alert-success">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_bot_enabled\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Enabled</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_bot_enabled" name="g_s_bot_enabled" type="checkbox" checked>\n' +
                '                        <label for="g_s_bot_enabled"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            <tr>\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_hide_actions\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Hide Actions</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_hide_actions" name="g_s_hide_actions" type="checkbox">\n' +
                '                        <label for="g_s_hide_actions"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            </tbody>\n' +
                '        </table>\n' +
                '    </div>\n' +
                '</form>'
        },
    };
    return {
        init : mounted.Initialize,
        viewBots : methods.viewBots,
        addBot : methods.addBot,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());