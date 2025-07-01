<button type="button" aria-haspopup="true" aria-expanded="false"
        data-bs-toggle="dropdown" class="p-0 me-2 btn btn-link">
    <span class="icon-wrapper icon-wrapper-alt rounded-circle">
        <span class="icon-wrapper-bg bg-success" id="notification-icon-bg"></span>
        <i id="notification-icon" class="fa fa-bell text-success"></i>
        <span id="notification-badge" class="badge badge-dot badge-dot-sm">0</span>
    </span>
</button>

<div tabindex="-1" role="menu" class="dropdown-menu-xl rm-pointers dropdown-menu dropdown-menu-right">
    <div class="scroll-area-lg">
        <div class="scrollbar-container ps">
            <div id="notification-list"></div>
            <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
                <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
            </div>
            <div class="ps__rail-y" style="top: 0px; right: 0px;">
                <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div>
            </div>
        </div>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item-btn text-center nav-item">
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{route('admin.web.my_account.notifications')}}"
                   class="btn-shadow btn-wide btn-pill btn btn-focus btn-sm">{{ __('main.Notification History') }}</a>
                <button id="mark-all-read-btn" type="button"
                        class="btn-shadow btn-wide btn-pill btn btn-success btn-sm">{{ __('main.Mark All Read') }}</button>
            </div>
        </li>
    </ul>
</div>

@section('js')
    @parent
    <script>
        let previousNotificationCount = parseInt(localStorage.getItem('notificationCount')) || 0;

        function playSound() {
            let audio = new Audio('{{url('puqcloud/beep.mp3')}}');
            audio.play().catch(function (error) {
                console.error('Error playing sound:', error);
            });
        }

        function showNotification(notificationCount) {
            if (Notification.permission === 'granted') {
                new Notification("You have new notifications!", {
                    body: `You have ${notificationCount} new notifications`,
                });
            } else {
                console.log('Notification permission denied or not granted yet.');
            }
        }

        function loadNotifications() {
            PUQajax(`{{ route('admin.api.my_account.bell_notification.get') }}`, {}, 500, null, 'GET')
                .then(function (response) {
                    $('#notification-icon-bg').removeClass('bg-danger').addClass('bg-success');
                    $('#notification-icon').removeClass('text-danger icon-anim-pulse').addClass('text-success');

                    if (response && Array.isArray(response.data)) {
                        let notifications = response.data;
                        let notificationList = $('#notification-list');
                        notificationList.empty();

                        let currentNotificationCount = notifications.length;

                        if (currentNotificationCount > 0) {
                            $('#notification-badge').text(currentNotificationCount);
                            $('#notification-icon-bg').addClass('bg-danger');
                            $('#notification-icon').addClass('text-danger icon-anim-pulse');
                            $('#mark-all-read-btn').show();

                            if (previousNotificationCount === 0) {
                                playSound();
                                showNotification(currentNotificationCount);
                            }

                            notifications.forEach(function (notification) {
                                let listItem = $('<div>');
                                listItem.html(`
                                    <div class="widget-content p-1 rounded shadow-sm border bg-light m-1">
                                        <div class="widget-content-left flex-grow-1">
                                            <div class="widget-heading fw-bold text-dark">${notification.subject || ''}</div>
                                            <div class="widget-subheading text-dark small">${linkify(notification.text_mini) || ''}</div>
                                        </div>
                                        <div class="widget-content-wrapper d-flex align-items-center">
                                            <div class="widget-content-left flex-grow-1">
                                                <div class="widget-subheading text-primary small">
                                                    <i class="fa fa-clock me-1"></i> ${formatDateWithoutTimezone(notification.created_at) || 'N/A'}
                                                </div>
                                            </div>
                                        <div class="widget-content-right">
                                            <button class="mark-read-btn border-0 btn-transition btn btn-outline-success btn-icon btn-icon-only"
                                                data-uuid="${notification.notification_status_uuid}" title="Mark as Read">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
`);
                                notificationList.append(listItem);
                            });
                        } else {
                            $('#mark-all-read-btn').hide();
                        }
                        localStorage.setItem('notificationCount', currentNotificationCount);
                        previousNotificationCount = currentNotificationCount;
                    } else {
                        localStorage.setItem('notificationCount', 0);
                        previousNotificationCount = 0;
                        $('#notification-badge').text('0');
                        $('#mark-all-read-btn').hide();
                        $('#notification-list').empty().append('<li class="list-group-item">' + translate('No new notifications') + '</li>');
                    }
                })
                .catch(function (error) {
                    $('#notification-badge').text('0');
                    $('#mark-all-read-btn').hide();
                    $('#notification-list').empty().append('<li class="list-group-item">' + translate('No new notifications') + '</li>');
                });
        }

        if (Notification.permission !== 'granted') {
            Notification.requestPermission().then(function (permission) {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                } else {
                    console.log('Notification permission denied');
                }
            });
        }

        function markAsRead(button, uuid) {
            PUQajax(`{{ route('admin.api.my_account.bell_notification.mark_read.get') }}`, {uuid: uuid}, 500, button, 'GET')
                .then(function () {
                    loadNotifications();
                })
                .catch(function (error) {
                    console.error('Error marking notification as read:', error);
                });
        }

        function markAllAsRead() {
            PUQajax(`{{ route('admin.api.my_account.bell_notification.mark_all_read.get') }}`, {}, 500, $('#mark-all-read-btn'), 'GET')
                .then(function () {
                    loadNotifications();
                })
                .catch(function (error) {
                    console.error('Error marking all notifications as read:', error);
                });
        }

        $(document).ready(function () {
            loadNotifications();
            setInterval(loadNotifications, 5000);

            $('#notification-list').on('click', '.mark-read-btn', function () {
                let uuid = $(this).data('uuid');
                markAsRead($(this), uuid);
            });

            $('#mark-all-read-btn').on('click', function () {
                markAllAsRead();
            });
        });
    </script>
@endsection
