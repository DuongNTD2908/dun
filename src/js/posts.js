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

    function openCommentModal(postId, postTitle, postContent) {
        let modal = document.getElementById('comment-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'comment-modal';
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
                <div style="width:90%;max-width:800px;background:#fff;border-radius:8px;padding:16px;max-height:90vh;overflow:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <h3 id="cmt-post-title" style="margin:0"></h3>
                        <button id="cmt-close" style="border:0;background:transparent;font-size:20px;cursor:pointer">✕</button>
                    </div>
                    <div id="cmt-post-content" style="color:#374151;margin-bottom:12px"></div>
                    <div id="cmt-list" style="margin-bottom:12px"></div>
                    <div id="cmt-form-wrap"></div>
                </div>`;
            document.body.appendChild(modal);
            document.getElementById('cmt-close').addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
        modal.style.display = 'flex';
        document.getElementById('cmt-post-title').textContent = postTitle || 'Bài viết';
        document.getElementById('cmt-post-content').textContent = postContent || '';
        document.getElementById('cmt-list').innerHTML = '<p class="muted">Đang tải bình luận…</p>';

        fetch('/DunWeb/controllers/comment.controller.php?action=list_json&post_id=' + encodeURIComponent(postId), {
            credentials: 'same-origin'
        })
            .then(async r => {
                const text = await r.text();
                try {
                    const json = JSON.parse(text === '' ? '[]' : text);
                    return { ok: r.ok, json };
                } catch (e) {
                    console.error('Comment list parse error:', e, 'raw:', text);
                    throw new Error('Invalid JSON from server');
                }
            })
            .then(({ ok, json: arr }) => {
                const wrap = document.getElementById('cmt-list');
                if (!arr || arr.length === 0) {
                    wrap.innerHTML = '<p class="muted">Chưa có bình luận nào.</p>';
                } else {
                    wrap.innerHTML = arr.map(c => `<div style="padding:8px;border-bottom:1px solid #eee"><strong>${escapeHtml(c.username)}</strong> <span style="color:#6b7280;font-size:12px">${c.created_at}</span><div style="margin-top:6px">${escapeHtml(c.content)}</div></div>`).join('');
                }
                const countSpan = document.querySelector('.comment-count[data-post-id="' + postId + '"]');
                if (countSpan) countSpan.textContent = (arr ? arr.length : 0);
            })
            .catch(err => {
                console.error('Failed to load comments for post', postId, err);
                document.getElementById('cmt-list').innerHTML = '<p class="muted">Không thể tải bình luận.</p>';
            });

        const formWrap = document.getElementById('cmt-form-wrap');
        if (CURRENT_USER_ID) {
            formWrap.innerHTML = `
                <form id="cmt-form">
                    <textarea id="cmt-input" rows="3" style="width:100%;padding:8px;margin-bottom:8px" placeholder="Viết bình luận..."></textarea>
                    <div style="text-align:right"><button type="submit" class="btn primary">Gửi</button></div>
                </form>`;
            const f = document.getElementById('cmt-form');
            f.addEventListener('submit', function(e) {
                e.preventDefault();
                const txt = document.getElementById('cmt-input').value.trim();
                if (!txt) return;
                const fd = new URLSearchParams();
                fd.append('action', 'add');
                fd.append('post_id', postId);
                fd.append('content', txt);
                fetch('/DunWeb/controllers/comment.controller.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                    .then(r => r.json())
                    .then(resp => {
                        if (resp && resp.status === 'ok' && resp.comment) {
                            const wrap = document.getElementById('cmt-list');
                            const node = document.createElement('div');
                            node.style.padding = '8px';
                            node.style.borderBottom = '1px solid #eee';
                            node.innerHTML = `<strong>${escapeHtml(resp.comment.username)}</strong> <span style="color:#6b7280;font-size:12px">${resp.comment.created_at}</span><div style="margin-top:6px">${escapeHtml(resp.comment.content)}</div>`;
                            if (wrap.firstChild) wrap.insertBefore(node, wrap.firstChild);
                            else wrap.appendChild(node);
                            const s = document.querySelector('.comment-count[data-post-id="' + postId + '"]');
                            if (s) s.textContent = (parseInt(s.textContent || '0', 10) + 1).toString();
                            document.getElementById('cmt-input').value = '';
                        } else {
                            alert('Không thể gửi bình luận.');
                        }
                    }).catch(err => {
                        console.error(err);
                        alert('Lỗi mạng.');
                    });
            });
        } else {
            formWrap.innerHTML = '<p class="muted">Vui lòng đăng nhập để bình luận.</p>';
        }
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
        span.dataset.postId = postId;
        fetch('/DunWeb/controllers/comment.controller.php?action=count&post_id=' + encodeURIComponent(postId), {
            credentials: 'same-origin'
        })
            .then(async r => {
                const text = await r.text();
                try {
                    const json = JSON.parse(text === '' ? '{"count":0}' : text);
                    return json;
                } catch (e) {
                    console.error('Count parse error for post', postId, e, 'raw:', text);
                    return { count: 0 };
                }
            })
            .then(o => {
                span.textContent = (o && o.count) ? o.count : '0';
            })
            .catch(err => {
                console.error('Count fetch error', err);
            });
        postWrap.addEventListener('click', function() {
            const card = this.closest('.post-card');
            const title = card ? (card.querySelector('.post-title')?.textContent || '') : '';
            const content = card ? (card.querySelector('p')?.textContent || '') : '';
            openCommentModal(postId, title, content);
        });
    });

    // Like/unlike behavior
    document.querySelectorAll('.like-btn').forEach(container => {
        const postId = container.dataset.postId;
        const img = container.querySelector('img.like');
        const countSpan = container.querySelector('.islike');
        if (!postId || !img || !countSpan) return;

        async function refresh() {
            try {
                const res = await fetch(`/DunWeb/controllers/like.controller.php?action=check&post_id=${postId}`, {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.ok) {
                    if (data.liked) {
                        img.classList.add('liked');
                        img.src = '/DunWeb/src/img/liked.png';
                    } else {
                        img.classList.remove('liked');
                        img.src = '/DunWeb/src/img/like.png';
                    }
                }

                const countRes = await fetch(`/DunWeb/controllers/like.controller.php?action=count&post_id=${postId}`, {
                    credentials: 'same-origin'
                });
                const countData = await countRes.json();
                if (countData.ok) countSpan.textContent = countData.count;
            } catch (err) {
                console.error('Like refresh error:', err);
            }
        }

        img.addEventListener('click', async () => {
            if (!CURRENT_USER_ID) {
                alert('Bạn cần đăng nhập để like bài viết.');
                return;
            }

            const action = img.classList.contains('liked') ? 'unlike' : 'like';
            const fd = new FormData();
            fd.append('action', action);
            fd.append('post_id', postId);

            const res = await fetch('/DunWeb/controllers/like.controller.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data.ok) {
                img.src = data.status === 'liked' ? '/DunWeb/src/img/liked.png' : '/DunWeb/src/img/like.png';
                countSpan.textContent = data.count;
            } else {
                alert(data.msg || 'Lỗi server.');
            }
        });

        refresh();
    });
};
