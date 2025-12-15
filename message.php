<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="src/img/logodun.png" type="image/x-icon">
    <link rel="stylesheet" href="src/css/message.css">
    <title>Dun - message</title>
</head>

<?php
require_once __DIR__ . '/config/session.php';
$currentUserId = $_SESSION['user_id'] ?? 0;
?>

<body>
    <div class="app" role="application" aria-label="Message">
        <div class="controller-nav">
            <div class="nav-menu">
                <div id="back">
                    <img src="src/img/back.png" alt="">
                </div>
                <div>
                    <img src="src/img/home.png" alt="">
                </div>
            </div>
            <div class="nav-menu">
                <div>
                    <img src="src/img/user.png" alt="">
                </div>
                <div>
                    <img src="src/img/setting.png" alt="">
                </div>
                <div id="darkModeToggle" class="nav-item-custom" title="Chế độ tối/sáng">
                    🌙
                </div>
            </div>
        </div>
        <script>
            document.getElementById("back").addEventListener("click", () => {
                window.location.href = "index.php";
            });
        </script>
        <!-- Left: chat list -->
        <aside class="sidebar" aria-label="Chat list">
            <div class="header">Đoạn chat
                <button class="icon-btn" title="New">＋</button>
            </div>
            <div class="search">
                <input aria-label="Search" placeholder="Tìm kiếm trên Message" />
            </div>
            <div class="chat-list" id="chatList" role="list">
                <!-- inbox will be rendered here -->
            </div>
        </aside>

        <!-- Right: chat area -->
        <main class="chat" aria-label="Chat window">
            <div class="chat-header">
                <button id="mobileBackBtn" class="icon-btn" style="display:none; margin-right: 8px;">←</button>
                <div class="avatar">Đ</div>
                <div style="flex:1">
                    <div id="chatHeaderName" style="font-weight:700">Chọn cuộc trò chuyện</div>
                    <div id="chatHeaderStatus" style="font-size:13px;color:var(--muted)">---</div>
                </div>
                <div style="display:flex;gap:8px">
                    <button class="icon-btn" title="More">⋯</button>
                </div>
            </div>

            <div class="messages" id="messages" aria-live="polite">

                <div class="msg-row left">

                </div>

                <div class="msg-row right">

                </div>

                <div class="msg-row left">

                </div>

                <div style="height:20px"></div>
            </div>

            <div class="compose">
                <input type="file" id="fileInput" style="display:none" accept="image/*,application/*" />
                <button class="icon-btn" title="Emoji">😊</button>
                <button id="attachBtn" class="icon-btn" title="Attach">📎</button>
                <input id="inputMsg" placeholder="Aa" aria-label="Type a message" />
                <button id="sendBtn" class="icon-btn" title="Send">➤</button>
            </div>

        </main>
    </div>

    <script>
        const messagesEl = document.getElementById('messages');
        const input = document.getElementById('inputMsg');
        const sendBtn = document.getElementById('sendBtn');
        const fileInput = document.getElementById('fileInput');
        const attachBtn = document.getElementById('attachBtn');
        const chatListEl = document.getElementById('chatList');
        const chatHeaderName = document.getElementById('chatHeaderName');
        const chatHeaderStatus = document.getElementById('chatHeaderStatus');
        const chatHeaderAvatar = document.querySelector('.chat-header .avatar');
        const searchInput = document.querySelector('.search input');
        const mobileBackBtn = document.getElementById('mobileBackBtn');
        const CURRENT_USER_ID = <?php echo (int)$currentUserId; ?>;
        let currentChatUserId = null; // other user's id for current conversation
        let attachedFile = null; // file selected via input / paste / drop
        let previewEl = null;

        function scrollBottom() {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        // open file picker when attach button clicked
        attachBtn.addEventListener('click', () => fileInput.click());

        // handle change on file input and set attachedFile
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) {
                attachedFile = fileInput.files[0];
                showAttachmentPreview(attachedFile);
            }
        });

        // show small preview of attached file in compose area
        function showAttachmentPreview(file) {
            removeAttachmentPreview();
            previewEl = document.createElement('div');
            previewEl.className = 'attachment-preview';
            previewEl.style.margin = '6px 0';
            previewEl.style.maxWidth = '200px';
            previewEl.style.display = 'flex';
            previewEl.style.alignItems = 'center';
            previewEl.style.gap = '8px';

            const label = document.createElement('div');
            label.style.fontSize = '13px';
            label.style.color = '#333';

            if (file.type && file.type.indexOf('image/') === 0) {
                const img = document.createElement('img');
                img.style.maxWidth = '200px';
                img.style.maxHeight = '140px';
                img.src = URL.createObjectURL(file);
                previewEl.appendChild(img);
                label.textContent = file.name || 'Ảnh đính kèm';
            } else {
                label.textContent = file.name || 'Tệp đính kèm';
            }
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'icon-btn';
            removeBtn.textContent = '✕';
            removeBtn.addEventListener('click', () => {
                attachedFile = null;
                fileInput.value = '';
                removeAttachmentPreview();
            });
            previewEl.appendChild(label);
            previewEl.appendChild(removeBtn);

            const compose = document.querySelector('.compose');
            compose.insertBefore(previewEl, compose.firstChild);
        }

        function removeAttachmentPreview() {
            if (previewEl && previewEl.parentNode) {
                previewEl.parentNode.removeChild(previewEl);
            }
            previewEl = null;
        }

        // paste image support: Ctrl+V or paste from clipboard
        document.addEventListener('paste', (e) => {
            if (!e.clipboardData) return;
            const items = e.clipboardData.items;
            if (!items) return;
            for (let i = 0; i < items.length; i++) {
                const it = items[i];
                if (it.type && it.type.indexOf('image') === 0) {
                    const blob = it.getAsFile();
                    if (blob) {
                        // create File with a sensible name
                        const file = new File([blob], 'pasted_' + Date.now() + '.png', {
                            type: blob.type
                        });
                        attachedFile = file;
                        showAttachmentPreview(file);
                        e.preventDefault();
                        return;
                    }
                }
            }
        });

        // drag & drop attachments onto compose area
        const composeArea = document.querySelector('.compose');
        composeArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });
        composeArea.addEventListener('drop', (e) => {
            e.preventDefault();
            const dt = e.dataTransfer;
            if (!dt) return;
            const f = dt.files && dt.files[0];
            if (f) {
                attachedFile = f;
                showAttachmentPreview(f);
            }
        });

        // check if we should start a conversation with a specific user (from URL)
        const urlParams = new URLSearchParams(window.location.search);
        const startUserId = urlParams.get('user_id');

        // load inbox on start
        function loadInbox(query = '') {
            return fetch('controllers/message.controller.php?action=inbox' + (query ? '&q=' + encodeURIComponent(query) : ''))
                .then(r => r.json())
                .then(list => {
                    renderInbox(list);
                    // If page requested to start a conversation with a specific user, open that one
                    if (startUserId) {
                        // prefer to open the requested user; if it's present in inbox, use its username
                        const found = list && list.find && list.find(x => String(x.other_user_id) === String(startUserId));
                        const uname = found ? (found.other_name && found.other_name.trim() !== '' ? (found.other_name + ' (' + (found.other_username || '') + ')') : found.other_username) : '';
                        openConversation(startUserId, uname);
                        return;
                    }
                    // otherwise open first conversation if exists
                    if (list && list.length) {
                        const first = list[0];
                        const uname = first.other_name && first.other_name.trim() !== '' ? (first.other_name + ' (' + (first.other_username || '') + ')') : first.other_username;
                        openConversation(first.other_user_id, uname);
                    }
                }).catch(e => console.error('Inbox load failed', e));
        }

        function renderInbox(list) {
            chatListEl.innerHTML = '';
            list.forEach(item => {
                const div = document.createElement('div');
                div.className = 'chat-item' + (item.unread_count && item.unread_count > 0 ? ' unread' : '');
                div.dataset.otherId = item.other_user_id;

                // avatar: prefer avatar URL from server, fallback to initial of username
                const avatar = document.createElement('div');
                avatar.className = 'avatar';
                if (item.other_avt && String(item.other_avt).trim() !== '') {
                    const img = document.createElement('img');
                    img.src = item.other_avt;
                    img.alt = item.other_username || item.other_name || 'User';
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    avatar.innerHTML = '';
                    avatar.appendChild(img);
                } else {
                    const initial = (item.other_username || item.other_name || 'U').charAt(0).toUpperCase();
                    avatar.textContent = initial;
                }

                const info = document.createElement('div');
                info.className = 'chat-info';
                const name = document.createElement('div');
                name.className = 'name';
                // display: full name (username) if full name exists, otherwise username
                if (item.other_name && String(item.other_name).trim() !== '') {
                    name.textContent = item.other_name + ' (' + (item.other_username || '') + ')';
                } else {
                    name.textContent = item.other_username || '—';
                }

                const meta = document.createElement('div');
                meta.className = 'meta';
                meta.textContent = (item.last_message || '').slice(0, 80);
                info.appendChild(name);
                // show unread badge if exists
                if (item.unread_count && item.unread_count > 0) {
                    const badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    badge.textContent = item.unread_count;
                    name.appendChild(badge);
                }
                const time = document.createElement('div');
                time.className = 'meta';
                time.textContent = item.last_sent_at ? new Date(item.last_sent_at).toLocaleString() : '';
                info.appendChild(name);
                info.appendChild(meta);
                info.appendChild(time);
                div.appendChild(avatar);
                div.appendChild(info);
                div.addEventListener('click', () => openConversation(item.other_user_id, item.other_username || item.other_name));
                chatListEl.appendChild(div);
            });
        }

        function openConversation(otherId, otherName) {
            currentChatUserId = otherId;
            // Mobile: Show chat view
            document.querySelector('.app').classList.add('chat-open');

            chatHeaderStatus.textContent = 'Đang tải...';
            messagesEl.innerHTML = '';
            fetch(`controllers/message.controller.php?action=conversation&with=${otherId}`)
                .then(r => r.json())
                .then(data => {
                    // server returns { meta: {iduser, username, name, avt}, messages: [...] }
                    const meta = data && data.meta ? data.meta : null;
                    const msgs = data && data.messages ? data.messages : (Array.isArray(data) ? data : []);

                    // update header: prefer full name
                    if (meta) {
                        const displayName = (meta.name && meta.name.trim() !== '') ? (meta.name + ' (' + (meta.username || '') + ')') : (meta.username || otherName || '—');
                        chatHeaderName.textContent = displayName;
                        // set avatar
                        if (meta.avt && meta.avt.trim() !== '') {
                            chatHeaderAvatar.innerHTML = '';
                            const img = document.createElement('img');
                            img.src = meta.avt;
                            img.alt = meta.username || meta.name || 'User';
                            img.style.width = '100%';
                            img.style.height = '100%';
                            img.style.objectFit = 'cover';
                            chatHeaderAvatar.appendChild(img);
                        } else {
                            chatHeaderAvatar.textContent = (meta.username || (meta.name || '')).charAt(0).toUpperCase();
                        }
                    } else {
                        chatHeaderName.textContent = otherName || '—';
                    }

                    chatHeaderStatus.textContent = 'Đã hoạt động';

                    msgs.forEach(m => renderMessage(m, (m.sender_id == CURRENT_USER_ID) ? 'right' : 'left'));
                    // mark unread as read for messages where current user is receiver
                    msgs.forEach(m => {
                        if (m.receiver_id == CURRENT_USER_ID && m.is_read == 0) {
                            fetch('controllers/message.controller.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `action=read&id=${encodeURIComponent(m.id)}`
                            });
                        }
                    });
                }).catch(e => console.error('Load conv failed', e));
        }

        function renderMessage(msg, side = 'right') {
            const row = document.createElement('div');
            row.className = `msg-row ${side}`;
            const wrapper = document.createElement('div');
            const bubble = document.createElement('div');
            bubble.className = `bubble ${side}`;
            // message text
            if (msg.content) {
                const textNode = document.createElement('div');
                textNode.textContent = msg.content;
                bubble.appendChild(textNode);
            }
            // attachment rendering: inline image preview for common image types
            if (msg.attachment_url) {
                const url = msg.attachment_url;
                const lower = url.toLowerCase();
                if (lower.match(/\.(png|jpe?g|gif|webp|bmp)$/)) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.style.maxWidth = '320px';
                    img.style.maxHeight = '240px';
                    img.style.display = 'block';
                    img.style.marginTop = '8px';
                    bubble.appendChild(img);
                } else {
                    const link = document.createElement('a');
                    link.href = url;
                    link.textContent = '[Tệp đính kèm]';
                    link.target = '_blank';
                    bubble.appendChild(document.createElement('br'));
                    bubble.appendChild(link);
                }
            }
            const ts = document.createElement('div');
            ts.className = 'timestamp';
            ts.textContent = msg.sent_at || new Date().toLocaleTimeString();
            wrapper.appendChild(bubble);
            wrapper.appendChild(ts);
            // add delete button for messages sent by current user
            if (msg.sender_id == CURRENT_USER_ID) {
                const del = document.createElement('button');
                del.className = 'icon-btn';
                del.style.width = '28px';
                del.style.height = '28px';
                del.style.marginLeft = '8px';
                del.textContent = '🗑';
                del.title = 'Xóa';
                del.addEventListener('click', () => {
                    if (!confirm('Bạn có muốn xóa tin nhắn này không?')) return;
                    fetch('controllers/message.controller.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=delete&id=${encodeURIComponent(msg.id)}`
                    }).then(r => r.json()).then(res => {
                        if (res.status === 'ok') {
                            // remove row
                            row.remove();
                            // refresh inbox
                            loadInbox();
                        }
                    });
                });
                wrapper.appendChild(del);
            }
            row.appendChild(wrapper);
            messagesEl.appendChild(row);
            scrollBottom();
        }

        sendBtn.addEventListener('click', () => {
            const text = input.value.trim();
            const file = attachedFile; // could be from file input, paste, or drop
            if (!text && !file) return;
            if (!currentChatUserId) {
                alert('Vui lòng chọn cuộc trò chuyện trước khi gửi.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('receiver_id', currentChatUserId);
            formData.append('content', text);
            if (file) formData.append('attachment', file);

            fetch('controllers/message.controller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderMessage({
                            content: text,
                            attachment_url: data.attachment_url,
                            sender_id: CURRENT_USER_ID,
                            sent_at: new Date().toLocaleTimeString()
                        }, 'right');
                        input.value = '';
                        fileInput.value = '';
                        attachedFile = null;
                        removeAttachmentPreview();
                        // refresh inbox to update last_message/unread
                        loadInbox();
                    } else {
                        alert('Gửi thất bại');
                    }
                }).catch(e => {
                    console.error(e);
                    alert('Lỗi kết nối');
                });
        });

        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadInbox(e.target.value.trim());
            }, 300);
        });

        // initial load
        loadInbox();

        // Dark Mode Logic
        const toggleBtn = document.getElementById('darkModeToggle');
        const body = document.body;

        // Check local storage
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
            toggleBtn.textContent = '☀️';
        }

        toggleBtn.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            toggleBtn.textContent = isDark ? '☀️' : '🌙';
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });

        // Mobile responsive logic
        function checkMobileView() {
            if (window.innerWidth <= 768) {
                mobileBackBtn.style.display = 'inline-flex';
            } else {
                mobileBackBtn.style.display = 'none';
                document.querySelector('.app').classList.remove('chat-open');
            }
        }
        window.addEventListener('resize', checkMobileView);
        checkMobileView();

        mobileBackBtn.addEventListener('click', () => {
            document.querySelector('.app').classList.remove('chat-open');
            currentChatUserId = null;
        });
        // Tạo hiệu ứng tuyết rơi (chỉ hiển thị khi có class dark-mode trong CSS)
        (function() {
            const colors = ['#fff', '#87CEEB', '#00BFFF'];
            for (let i = 0; i < 25; i++) { // Số lượng ít (tần suất thấp)
                const flake = document.createElement('div');
                flake.className = 'snowflake';
                flake.textContent = '.';
                flake.style.left = Math.random() * 100 + 'vw';
                flake.style.animationDuration = (Math.random() * 10 + 10) + 's'; // Tốc độ rơi chậm
                flake.style.animationDelay = Math.random() * 5 + 's';
                flake.style.fontSize = (Math.random() * 20 + 20) + 'px';
                flake.style.color = colors[Math.floor(Math.random() * colors.length)];
                document.body.appendChild(flake);
            }
        })();
    </script>
</body>

</html>
</div>