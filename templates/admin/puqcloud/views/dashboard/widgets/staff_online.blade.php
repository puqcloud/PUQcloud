@foreach($admins as $admin)
    <div class="widget-content p-0">
        <div class="widget-content-wrapper">
            <div class="widget-content-left me-3">
                <div class="avatar-icon-wrapper">
                    <div class="badge badge-bottom"></div>
                    <div class="avatar-icon rounded">
                        <img src="{{$admin['gravatar']}}" alt="">
                    </div>
                </div>
            </div>
            <div class="widget-content-left">
                <div class="widget-heading">
                    <a href="{{route('admin.web.admin',$admin['uuid'])}}"> {{$admin['name']}}</a>
                </div>
                <div class="widget-subheading">{{$admin['last_seen']}}</div>
            </div>
        </div>
    </div>
@endforeach
