/**
 * Initialize all post event listeners
 * This function can be called multiple times (e.g., after AJAX load)
 */
window.initPostEventListeners = function() {
    const CURRENT_USER_ID = window.CURRENT_USER_ID || 0;

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[m];
        });
    }

    // Setup global send button listener once
    const sendBtn = document.getElementById('send-comment');
    const commentInput = document.getElementById('comment-input');
    
    if (sendBtn && !sendBtn.dataset.hasListener) {
        sendBtn.dataset.hasListener = 'true';
        sendBtn.addEventListener('click', () => {
            const postId = sendBtn.dataset.currentPostId;
            const content = commentInput.value.trim();
            if (!postId || !content) return;
            if (!CURRENT_USER_ID) {
                alert('Vui lòng đăng nhập');
                return;
            }

            const fd = new URLSearchParams();
            fd.append('action', 'add');
            fd.append('post_id', postId);
            fd.append('content', content);

            fetch('/DunWeb/controllers/comment.controller.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(resp => {
                if (resp.status === 'ok') {
                    commentInput.value = '';
                    // Refresh comments
                    openCommentModal(postId);
                } else {
                    alert(resp.msg || 'Lỗi gửi bình luận');
                }
            });
        });
        
        if (commentInput) {
            commentInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendBtn.click();
            });
        }
    }

    function openCommentModal(postId) {
        const modal = document.getElementById('comment-modal');
        const listWrap = document.getElementById('comment-body');
        const countSpan = document.querySelector(`.comment-count[data-post-id="${postId}"]`);

        if (!modal || !listWrap) return;

        // Set current post ID for send button
        if (sendBtn) sendBtn.dataset.currentPostId = postId;

        // Show modal (using class to match index.php logic)
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Reset list
        listWrap.innerHTML = '<div class="loading-spinner"></div>';

        fetch('/DunWeb/controllers/comment.controller.php?action=list_json&post_id=' + encodeURIComponent(postId), {
            credentials: 'same-origin'
        })
            .then(async r => {
                const text = await r.text();
                try {
                    return JSON.parse(text === '' ? '[]' : text);
                } catch (e) {
                    console.error('Comment list parse error:', e, 'raw:', text);
                    return [];
                }
            })
            .then(arr => {
                if (!arr || arr.length === 0) {
                    listWrap.innerHTML = '<div class="empty-state"><p>Chưa có bình luận nào. Hãy là người đầu tiên!</p></div>';
                } else {
                    listWrap.innerHTML = arr.map(c => `
                        <div style="padding:12px 0;border-bottom:1px solid #f0f0f0;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                <strong style="font-size:14px;">${escapeHtml(c.username)}</strong>
                                <span style="font-size:12px;color:#888;">${c.created_at}</span>
                            </div>
                            <div style="font-size:14px;color:#333;line-height:1.4;">${escapeHtml(c.content)}</div>
                        </div>
                    `).join('');
                }
                if (countSpan) countSpan.textContent = arr.length;
            })
            .catch(err => {
                console.error('Failed to load comments for post', postId, err);
                listWrap.innerHTML = '<p style="color:red;text-align:center">Lỗi tải bình luận.</p>';
            });
    }

    // Follow/unfollow logic
    document.querySelectorAll('.post-card .follow').forEach(btn => {
        const targetUserId = btn.dataset.userId ? parseInt(btn.dataset.userId, 10) : 0;
        async function refreshState() {
            if (!CURRENT_USER_ID || !targetUserId) return;
            try {
                const form = new URLSearchParams();
                form.append('action', 'is_following');
                form.append('following_id', targetUserId);
                const res = await fetch('/DunWeb/controllers/follow.controller.php', {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                });
                const j = await res.json();
                if (j && j.is_following) {
                    btn.textContent = 'Đang theo dõi';
                    btn.classList.add('primary');
                } else {
                    btn.textContent = 'Theo dõi';
                    btn.classList.remove('primary');
                }
            } catch (e) {
                console.error('follow state error', e);
            }
        }
        if (CURRENT_USER_ID) refreshState();
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (!CURRENT_USER_ID) {
                const loginBtn = document.querySelector('.btn-login');
                const loginModal = document.getElementById('login-modal');
                if (loginBtn) loginBtn.click();
                else if (loginModal) {
                    loginModal.classList.add('active');
                    loginModal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
                return;
            }
            const isFollowing = btn.textContent.trim().toLowerCase().includes('đang');
            if (isFollowing && !confirm('Bạn có chắc muốn hủy theo dõi?')) return;
            const action = isFollowing ? 'unfollow' : 'follow';
            const form = new URLSearchParams();
            form.append('action', action);
            form.append('following_id', targetUserId);
            try {
                const res = await fetch('/DunWeb/controllers/follow.controller.php', {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin'
                });
                const raw = await res.text();
                let j = null;
                try {
                    j = raw ? JSON.parse(raw) : {};
                } catch (parseErr) {
                    console.error('Follow API non-JSON response:', raw);
                    alert(raw || 'Lỗi server.');
                    return;
                }
                if (res.ok && (j.status === 'followed' || j.status === 'unfollowed')) {
                    if (action === 'follow') {
                        btn.textContent = 'Đang theo dõi';
                        btn.classList.add('primary');
                    } else {
                        btn.textContent = 'Theo dõi';
                        btn.classList.remove('primary');
                    }
                } else {
                    console.error('Follow API error JSON:', j);
                    alert(j.message || j.error || 'Không thể thay đổi trạng thái theo dõi.');
                }
            } catch (err) {
                console.error('Follow request failed:', err);
                alert('Lỗi mạng. Vui lòng thử lại.');
                setTimeout(() => window.location.reload(), 300);
            }
        });
    });

    // Report button handling
    document.querySelectorAll('.report-btn').forEach(btn => {
        const postId = btn.dataset.postId;
        btn.addEventListener('click', () => {
            if (!CURRENT_USER_ID) {
                const loginBtn = document.querySelector('.btn-login');
                if (loginBtn) loginBtn.click();
                else alert('Vui lòng đăng nhập để báo cáo.');
                return;
            }
            let modal = document.getElementById('report-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'report-modal';
                modal.style.position = 'fixed';
                modal.style.left = '0';
                modal.style.top = '0';
                modal.style.right = '0';
                modal.style.bottom = '0';
                modal.style.background = 'rgba(0,0,0,0.5)';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';
                modal.innerHTML = `
                    <div style="width:90%;max-width:520px;background:#fff;border-radius:8px;padding:18px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <h3 style="margin:0">Báo cáo bài viết</h3>
                            <button id="report-close" style="border:0;background:transparent;font-size:20px;cursor:pointer">✕</button>
                        </div>
                        <div style="margin-bottom:8px;color:#374151">Vui lòng mô tả lý do bạn muốn báo cáo bài viết này. Chúng tôi sẽ xem xét và xử lý.</div>
                        <form id="report-form">
                            <textarea id="report-reason" rows="4" style="width:100%;padding:8px;border:1px solid #eee;border-radius:6px" placeholder="Lý do..." required></textarea>
                            <div style="text-align:right;margin-top:10px">
                                <button type="submit" class="btn primary">Gửi báo cáo</button>
                            </div>
                        </form>
                        <div id="report-msg" style="margin-top:8px;font-size:14px;color:#374151"></div>
                    </div>`;
                document.body.appendChild(modal);
                document.getElementById('report-close').addEventListener('click', () => modal.style.display = 'none');
            }
            modal.style.display = 'flex';
            const form = document.getElementById('report-form');
            const reasonEl = document.getElementById('report-reason');
            const msgEl = document.getElementById('report-msg');
            form.onsubmit = function(e) {
                e.preventDefault();
                const reason = reasonEl.value.trim();
                if (!reason) return;
                const body = new URLSearchParams();
                body.append('post_id', postId);
                body.append('reason', reason);
                fetch('/DunWeb/controllers/report.controller.php?action=report', {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin'
                }).then(async r => {
                    const text = await r.text();
                    try {
                        const j = JSON.parse(text === '' ? '{}' : text);
                        if (j.ok) {
                            msgEl.style.color = 'green';
                            msgEl.textContent = 'Cảm ơn — báo cáo đã được gửi.';
                            reasonEl.value = '';
                            setTimeout(() => modal.style.display = 'none', 1200);
                        } else {
                            msgEl.style.color = '#b91c1c';
                            msgEl.textContent = j.msg || 'Không thể gửi báo cáo.';
                        }
                    } catch (e) {
                        msgEl.style.color = '#b91c1c';
                        msgEl.textContent = 'Lỗi server.';
                    }
                }).catch(err => {
                    console.error('Report submit failed', err);
                    msgEl.style.color = '#b91c1c';
                    msgEl.textContent = 'Lỗi mạng. Vui lòng thử lại.';
                });
            };
        });
    });

    // Initialize comment counts and click handlers
    document.querySelectorAll('.comment-count').forEach(span => {
        const postWrap = span.closest('.comment-btn');
        const postId = postWrap ? postWrap.dataset.postId : null;
        if (!postId) return;
        
        // Avoid duplicate listeners
        if (postWrap.dataset.listenerAttached) return;
        postWrap.dataset.listenerAttached = 'true';

        fetch('/DunWeb/controllers/comment.controller.php?action=count&post_id=' + encodeURIComponent(postId), {
            credentials: 'same-origin'
        })
            .then(async r => {
                const text = await r.text();
                try {
                    return JSON.parse(text === '' ? '{"count":0}' : text);
                } catch (e) {
                    return { count: 0 };
                }
            })
            .then(o => {
                span.textContent = (o && o.count) ? o.count : '0';
            })
            .catch(err => {
                console.error('Count fetch error', err);
            });
        postWrap.addEventListener('click', function(e) {
            e.stopPropagation();
            openCommentModal(postId);
        });
    });

    // Like/unlike behavior
    document.querySelectorAll('.like-btn').forEach(container => {
        const postId = container.dataset.postId;
        const img = container.querySelector('img.like');
        // Sửa selector từ .islike thành .like-count để khớp với post-card.php
        const countSpan = container.querySelector('.like-count');
        if (!postId || !img || !countSpan) return;

        // Gán sự kiện click vào cả container nút like để dễ bấm hơn
        container.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            if (!CURRENT_USER_ID) {
                alert('Bạn cần đăng nhập để like bài viết.');
                return;
            }

            // Lấy trạng thái hiện tại từ data attribute (được render từ server)
            const isLiked = container.dataset.liked == '1';
            const action = isLiked ? 'unlike' : 'like';
            
            // --- Optimistic UI Update (Cập nhật giao diện ngay lập tức) ---
            const newLikedState = !isLiked;
            container.dataset.liked = newLikedState ? '1' : '0';
            
            // Đổi ảnh
            img.src = newLikedState ? '/DunWeb/src/img/liked.png' : '/DunWeb/src/img/like.png';
            
            // Tăng/giảm số lượng tạm thời
            let currentCount = parseInt(countSpan.textContent) || 0;
            if (newLikedState) {
                currentCount++;
            } else {
                currentCount = Math.max(0, currentCount - 1);
            }
            countSpan.textContent = currentCount;

            // --- Gửi request lên server ---
            const fd = new FormData();
            fd.append('action', action);
            fd.append('post_id', postId);

            try {
                const res = await fetch('/DunWeb/controllers/like.controller.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.ok) {
                    // Cập nhật lại số lượng chính xác từ server
                    countSpan.textContent = data.count;
                } else {
                    // Nếu lỗi, hoàn tác lại giao diện
                    console.error('Like error:', data.msg);
                    container.dataset.liked = isLiked ? '1' : '0';
                    img.src = isLiked ? '/DunWeb/src/img/liked.png' : '/DunWeb/src/img/like.png';
                    // Hoàn tác số lượng (đơn giản là load lại trang hoặc tính ngược lại, ở đây ta để server sync lần sau)
                    alert(data.msg || 'Lỗi server.');
                }
            } catch (err) {
                console.error('Network error:', err);
                // Hoàn tác khi lỗi mạng
                container.dataset.liked = isLiked ? '1' : '0';
                img.src = isLiked ? '/DunWeb/src/img/liked.png' : '/DunWeb/src/img/like.png';
            }
        });
    });
};
