/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

// window.$ = window.jQuery = require('jquery');
let token = document.head.querySelector('meta[name="csrf-token"]');
window.Laravel = { csrfToken: token.content };


// require('bootstrap-sass');
try {
    window.Popper = require('popper.js').default;
    require('bootstrap');
} catch (e) {}

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */



if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

/**
 * Load dependencies used across this app
 * Autolinker makes text links clickable
 * Datatables turns a table into a JS instance with sorting
 * Toastr is used for notification popups
 * Autosize allows textareas to grow dynamically
 * Validator is for BS3 and adds validation to forms using html5 attr
 */
window.uuid = require('uuid');
window.Autolinker = require('autolinker');
import dt from 'datatables.net-bs4';
window.dt = require( 'datatables.net');
window.toastr = require('toastr');
window.moment = require('moment');
import 'simplebar';

/**
 * Now we need to load in our global app controllers (Routers)
 * Messenger holds global methods and common data
 * PageListeners holds events and watchers to be called
 * on any page at any time
 */

import {Messenger} from './Messenger';
import {PageListeners} from './managers/PageListeners';
import {InactivityManager} from './managers/InactivityManager';
import {CallManager} from './managers/CallManager';
import {NotifyManager} from './managers/NotifyManager';
import {FriendsManager} from './managers/FriendsManager';
import {ThreadManager} from './managers/ThreadManager';
import {ThreadBots} from './modules/ThreadBots';
import {ThreadTemplates} from './templates/ThreadTemplates';
import {MessengerSettings} from './modules/MessengerSettings';
import {RecordAudio} from './modules/RecordAudio';
import {InviteJoin} from './modules/InviteJoin';
import {EmojiPicker} from './modules/EmojiPicker';