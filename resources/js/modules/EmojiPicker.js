import { EmojiButton } from '@joeattardi/emoji-button';

window.EmojiPicker = (function () {
    var opt = {
        lock : true,
        using : false,
        elements : {

        },
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
        },
    },
    methods = {
        addReaction : function(messageId){
            let message = document.getElementById('message_'+messageId).getElementsByClassName('message-body')[0];
            let picker = new EmojiButton({
                theme: 'dark',
                autoHide : false,
            });
            picker.showPicker(message);
            picker.on('emoji', selection => {
                // `selection` object has an `emoji` property
                // containing the selected emoji
                console.log(selection);
            });
            picker.on('hidden', () => {
                picker.destroyPicker()
            });
        },
        addMessage : function(){

        }
    },
    templates = {

    };
    return {
        init : mounted.Initialize,
        addReaction : methods.addReaction,
        addMessage : methods.addMessage,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());