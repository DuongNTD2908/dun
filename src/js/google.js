const GOOGLE_CLIENT_ID =
  "838218617953-99i30eqhr8m5e395953thqv7tipk7oka.apps.googleusercontent.com";

function handleCredentialResponse(response) {
  // Received ID token from Google Identity Services.
  // Send it to the server to verify and create a session.
  const idToken = response.credential;
  if (!idToken) return console.error("No ID token received from Google");

  console.log("[google.js] Sending ID token to server...");

  // Use absolute path from root
  const url = window.location.origin + '/DunWeb/controllers/user.controller.php?action=google';
  console.log("[google.js] Fetch URL:", url);

  fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ id_token: idToken }),
    credentials: "same-origin",
  })
    .then(async (res) => {
      console.log("[google.js] Response status:", res.status);
      const text = await res.text();
      console.log("[google.js] Response text:", text);
      
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        console.error("[google.js] Failed to parse JSON:", e);
        alert("Lỗi: phản hồi từ server không hợp lệ");
        return;
      }
      
      if (json.ok) {
        console.log("[google.js] Login successful!");
        // Close modal then reload to show logged-in state
        const modal = document.getElementById("login-modal");
        if (modal) {
          modal.classList.remove("active");
          modal.setAttribute("aria-hidden", "true");
          document.body.style.overflow = "";
        }
        // redirect if server provided URL, otherwise reload
        if (json.redirect) {
          console.log("[google.js] Redirecting to:", json.redirect);
          window.location.href = json.redirect;
        } else {
          console.log("[google.js] Reloading page...");
          window.location.reload();
        }
      } else {
        console.error("[google.js] Login failed:", json);
        alert(json.msg || "Đăng nhập Google thất bại");
      }
    })
    .catch((err) => {
      console.error("[google.js] Network error:", err);
      alert("Lỗi kết nối mạng: " + err.message);
    });
}

function insertGoogleLoginIntoDialog() {
  const dialog = document.querySelector("#login-modal .dialog");
  if (!dialog) return;
  const form = dialog.querySelector("#login-form");
  if (!form) return;

  // Don't insert twice
  if (form.querySelector(".social-login")) return;

  const container = document.createElement("div");
  container.className = "social-login";
  container.innerHTML = `
            <div class="divider" aria-hidden="true" style="display:flex;align-items:center;gap:10px;margin:12px 0;color:#666">
              <span style="flex:1;height:1px;background:#eee;display:block"></span>
              <span style="font-size:13px;color:#666">Hoặc</span>
              <span style="flex:1;height:1px;background:#eee;display:block"></span>
            </div>
              <div id="google-signin-wrapper" style="width:100%;display:flex;justify-content:center"></div>
              <div style="margin-top:8px;text-align:center">
                <!-- Single unified CTA: falls back to server-side OAuth if GSI can't render -->
                <a id="google-fallback-cta" href="/DunWeb/config/google.php" class="btn" style="display:inline-block;padding:8px 12px;border:1px solid #ddd;border-radius:6px;background:#fff;color:#222;text-decoration:none;">Đăng nhập bằng Google</a>
              </div>
                `;

  const controls = form.querySelector(".controls");
  if (controls && controls.parentNode) {
    controls.parentNode.insertBefore(container, controls.nextSibling);
  } else {
    form.appendChild(container);
  }
}

window.addEventListener("load", () => {
  insertGoogleLoginIntoDialog();

  function initGSI() {
    // Debug: show origin and client id to help fix "origin not allowed" issues
    console.log('[google.js] initGSI - origin:', window.location.origin, 'client_id:', GOOGLE_CLIENT_ID);

    if (typeof google !== "undefined" && google.accounts && google.accounts.id) {
      // Render button only after logging debug info
      google.accounts.id.initialize({
        client_id: GOOGLE_CLIENT_ID,
        callback: handleCredentialResponse,
      });

      const wrapper = document.getElementById('google-signin-wrapper');
      const fallback = document.getElementById('google-fallback-cta');
      // Attempt to render the native GSI button into the wrapper. If it fails
      // (e.g. origin not authorized) keep the fallback CTA visible.
      try {
        // hide fallback while we try to render the native button
        if (fallback) fallback.style.display = 'none';
        google.accounts.id.renderButton(wrapper, {
          theme: 'outline',
          size: 'large',
          text: 'continue_with',
        });
        // Also add a small aria-label for accessibility
        wrapper.setAttribute('aria-label', 'Sign in with Google');
      } catch (e) {
        console.error('[google.js] Failed to render Google button:', e);
        // show fallback CTA
        if (fallback) fallback.style.display = '';
        console.info('[google.js] If you see "The given origin is not allowed for the given client ID",');
        console.info('[google.js] open Google Cloud Console → Credentials, select the OAuth client,');
        console.info('[google.js] and add this origin to "Authorized JavaScript origins":', window.location.origin);
      }
    } else {
      console.warn("Google Identity Services not loaded.");
    }
  }

  // Ensure GSI script is present and initialize when loaded
  const existing = document.querySelector(
    'script[src="https://accounts.google.com/gsi/client"]'
  );
  if (existing) {
    if (existing.complete || existing.readyState === "loaded") initGSI();
    else existing.addEventListener("load", initGSI);
  } else {
    const s = document.createElement("script");
    s.src = "https://accounts.google.com/gsi/client";
    s.async = true;
    s.defer = true;
    s.onload = initGSI;
    document.head.appendChild(s);
  }
});
