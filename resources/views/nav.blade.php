<ul class="navbar-nav mr-auto">

</ul>
<ul class="navbar-nav mb-1">
    <li id="active_calls_nav" class="nav-item dropdown mx-1 my-2 my-lg-0">
        <a id="active_calls_link" href="#" class="dropdown-toggle nav-link pt-1 pb-0" data-toggle="dropdown" role="button" aria-expanded="false">
            <i id="active_call_nav_icon" class="fas fa-video fa-2x"></i>
            <span id="nav_calls_count" class="badge badge-pill badge-danger badge-notify"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right notify-drop bg-light" aria-labelledby="click_friends_tab">
            <div class="col-12 text-center">
                <h6 class="font-weight-bold">Active Calls</h6>
                <hr class="mt-n1 mb-2">
            </div>
            <div id="active_calls_ctnr" class="drop-content list-group">
                <div class="col-12 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
            </div>
            <div class="col-12 text-center mt-2">
                <hr class="mb-1 mt-0">
            </div>
        </div>
    </li>
    <li class="nav-item mx-1 {{request()->route()->getName() === 'messenger.portal' ? 'active' : ''}}">
        <a class="nav-link pt-1 pb-0" href="{{ route('messenger.portal') }}">
            <i class="fas fa-comment fa-2x"></i>
            <span id="nav_thread_count" class="badge badge-pill badge-danger badge-notify"></span>
        </a>
    </li>
    <li id="pending_friends_nav" class="nav-item dropdown mx-1 my-2 my-lg-0">
        <a id="click_friends_tab" href="#" class="dropdown-toggle nav-link pt-1 pb-0" data-toggle="dropdown" role="button" aria-expanded="false">
            <i class="fas fa-user-friends fa-2x"></i>
            <span id="nav_friends_count" class="badge badge-pill badge-danger badge-notify"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right notify-drop bg-light" aria-labelledby="click_friends_tab">
            <div class="row">
                <div class="col-12 pill-tab-nav">
                    <nav id="nav-friend-tabs" class="nav nav-pills flex-column flex-sm-row" role="tablist">
                        <a class="flex-sm-fill text-sm-center nav-link h6 active" id="tab-pending" data-toggle="pill" href="#f_pending" role="tab" aria-controls="f_pending" aria-selected="true"><i class="fas fa-user-friends"></i> Pending</a>
                        <a class="flex-sm-fill text-sm-center nav-link h6" id="tab-sent" data-toggle="pill" href="#f_sent" role="tab" aria-controls="f_sent" aria-selected="false"><i class="fas fa-user-friends"></i> Sent</a>
                    </nav>
                </div>
            </div>
            <div class="tab-content">
                <div id="f_pending" class="tab-pane fade show active">
                    <div id="pending_friends_ctnr" class="drop-content list-group">
                        <div class="col-12 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
                    </div>
                </div>
                <div id="f_sent" class="tab-pane fade">
                    <div id="sent_friends_ctnr" class="drop-content list-group">
                        <div class="col-12 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
                    </div>
                </div>
            </div>
            <div class="col-12 text-center mt-2 pb-4 pb-lg-3">
                <hr class="mb-1 mt-0">
                <span class="float-right"><a class="nav-search-link text-dark" href="#"><i class="fas fa-search"></i> Find Friends</a></span>
            </div>
        </div>
    </li>
    <li class="nav-item dropdown">
        <a id="user_nav_dp" href="#" class="dropdown-toggle nav-link pb-lg-0" data-toggle="dropdown" role="button" aria-expanded="false">
            <img class="rounded align-top my-n2 my-global-avatar" id="navProf_pic" height="38" width="38" src="{{messenger()->getProvider()->getAvatarRoute()}}">
            <i class="h5 fas fa-caret-down"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="user_nav_dp">
            <a onclick="Messenger.forms().Logout(); return false;" class="dropdown-item"  href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </li>
</ul>