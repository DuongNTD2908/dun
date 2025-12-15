<?php if (!empty($posts)): ?>
    <?php foreach ($posts as $post): ?>
        <div class="post-card" id="postContainer">
            <div class="post-item">
                <div class="post-header">
                    <?php
                    $postUserId = $post["user_id"] ?? $post["iduser"] ?? $post["userId"] ?? $post["uid"] ?? 0;
                    $postUserName = $post["name"] ?? $post["username"] ?? "";
                    $postAvatar = $post["avt"] ?? "";
                    ?>
                    <?php if ($postAvatar): ?>
                        <a class="avatar-link" href="profile.php?id=<?php echo (int)$postUserId; ?>">
                            <img class="avatar" src="<?php echo htmlspecialchars($postAvatar); ?>" alt="">
                        </a>
                    <?php else: ?>
                        <a class="avatar-link" href="profile.php?id=<?php echo (int)$postUserId; ?>">
                            <div class="avatar"></div>
                        </a>
                    <?php endif; ?>
                    <div class="meta">
                        <div class="user"><a class="user-link" href="profile.php?id=<?php echo (int)$postUserId; ?>"><?php echo htmlspecialchars($postUserName); ?></a></div>
                        <div class="sub"><?php echo htmlspecialchars($post["topic_name"]); ?></div>
                    </div>
                    <?php if (isset($_SESSION["user_id"]) && $_SESSION["user_id"]): ?>
                        <?php if ((int)$_SESSION["user_id"] !== (int)$postUserId): ?>
                            <button class="follow" data-user-id="<?php echo (int)$postUserId; ?>">Theo d?i</button>
                        <?php else: ?>
                            <!-- Do not show follow button for the post owner -->
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="follow btn-login">Theo d?i</button>
                    <?php endif; ?>
                </div>
                <h3 class="post-title"><?php echo htmlspecialchars($post["title"]); ?></h3>
                <p><?php echo htmlspecialchars($post["content"]); ?></p>
                <?php if (!empty($post["image_url"])): ?>
                <div class="post-images">
                    <div class="img"><img src="<?php echo htmlspecialchars($post["image_url"]); ?>" width="100%" alt=""></div>
                </div>
                <?php endif; ?>
                <div class="post-footer">
                    <div class="icons">
                        <div class="like-btn" data-post-id="<?php echo (int)$post["idpost"] ?? (int)$post["id"]; ?>" data-liked="<?php echo $post['is_liked'] ?? 0; ?>" style="cursor:pointer">
                            <img class="like" src="<?php echo !empty($post['is_liked']) ? '/DunWeb/src/img/liked.png' : '/DunWeb/src/img/like.png'; ?>" alt="" width="70%">
                            <span class="like-count"><?php echo $post['likes_count'] ?? 0; ?></span>
                        </div>
                        <div class="comment-btn" data-post-id="<?php echo (int)$post["idpost"] ?? (int)$post["id"]; ?>" style="cursor:pointer">
                            <img src="/DunWeb/src/img/comment.png" alt="" width="70%"><span class="comment-count"><?php echo $post['comments_count'] ?? 0; ?></span>
                        </div>
                        <div class="report-btn" data-post-id="<?php echo (int)$post["idpost"] ?? (int)$post["id"]; ?>" style="cursor:pointer">
                            <img src="/DunWeb/src/img/report.png" alt="" width="70%"><span>Report</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No posts found.</p>
<?php endif; ?>
