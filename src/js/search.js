(function () {
  const input = document.querySelector('#search-form input[name="q"]');
  if (!input) return;
  // create container
  const dd = document.createElement("div");
  dd.className = "search-dropdown";
  dd.style.display = "none";
  document.body.appendChild(dd);

  let fetched = false;
  let lastPos = null;

  function positionDropdown() {
    const rect = input.getBoundingClientRect();
    dd.style.left = rect.left + window.scrollX + "px";
    dd.style.top = rect.bottom + window.scrollY + 6 + "px";
    dd.style.minWidth = Math.max(220, rect.width) + "px";
  }

  async function fetchHistory() {
    try {
      const res = await fetch(
        "controllers/search.controller.php?action=history&limit=10",
        {
          credentials: "same-origin",
        }
      );
      if (res.status === 401) {
        // Người dùng chưa đăng nhập, không làm gì cả
        return [];
      }
      if (!res.ok) return [];
      const j = await res.json();
      return j.ok ? j.history : [];
    } catch (e) {
      return [];
    }
  }

  async function deleteHistoryItem(id) {
    try {
      const formData = new FormData();
      formData.append("action", "delete_item");
      formData.append("id", id);

      const res = await fetch("controllers/search.controller.php", {
        method: "POST",
        credentials: "same-origin",
        body: formData,
      });
      if (!res.ok) return false;
      const j = await res.json();
      return j.ok;
    } catch (e) {
      return false;
    }
  }

  // *** HÀM MỚI: Gửi yêu cầu xóa tất cả ***
  async function clearAllHistory() {
    try {
      const formData = new FormData();
      formData.append("action", "clear_all");

      const res = await fetch("controllers/search.controller.php", {
        method: "POST",
        credentials: "same-origin",
        body: formData,
      });
      if (!res.ok) return false;
      const j = await res.json();
      return j.ok;
    } catch (e) {
      return false;
    }
  }

  // *** HÀM ĐƯỢC CẬP NHẬT: showDropdown ***
  async function showDropdown() {
    // if (!isLoggedIn) return; // Chúng ta để server kiểm tra
    positionDropdown();
    dd.innerHTML = '<div style="padding:10px;color:#666">Đang tải...</div>';
    dd.style.display = "block";
    const hist = await fetchHistory();

    if (!hist || hist.length === 0) {
      dd.innerHTML =
        '<div style="padding:10px;color:#666">Chưa có lịch sử tìm kiếm</div>';
      return;
    }

    // Header
    const header = document.createElement("div");
    header.style.padding = "8px 16px";
    header.style.fontSize = "14px";
    header.style.fontWeight = "600";
    header.style.color = "#65676b";
    header.textContent = "Gần đây";
    dd.appendChild(header);

    const ul = document.createElement("ul");
    hist.forEach((h) => {
      const li = document.createElement("li");

      // Phần nội dung (text + ngày)
      const contentDiv = document.createElement("div");
      contentDiv.className = "hist-content";
      contentDiv.innerHTML =
        '<i class="fa fa-clock-o" style="margin-right:12px;color:#65676b;font-size:16px;"></i>' +
        '<div style="display:flex;flex-direction:column;overflow:hidden;">' +
        '<strong style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(h.query_text) + '</strong>' +
        '<span style="font-size:12px;color:#65676b;">' + h.created_at + '</span></div>';

      // Nút xóa 'x'
      const deleteBtn = document.createElement("span");
      deleteBtn.className = "delete-hist";
      deleteBtn.innerHTML = "&times;"; // Ký tự 'x'
      deleteBtn.title = "Xóa mục này";

      li.appendChild(contentDiv);
      li.appendChild(deleteBtn);

      // Sự kiện click vào nội dung -> tìm kiếm
      contentDiv.addEventListener("click", function () {
        input.value = h.query_text;
        document.getElementById("search-form").submit();
      });

      // Sự kiện click vào nút xóa -> xóa
      deleteBtn.addEventListener("click", async function (e) {
        e.stopPropagation(); // Ngăn sự kiện click của 'li' (nếu có)
        if (await deleteHistoryItem(h.id)) {
          // Xóa khỏi UI
          li.remove();
          // Kiểm tra nếu ul rỗng
          if (ul.children.length === 0) {
            dd.innerHTML =
              '<div style="padding:10px;color:#666">Chưa có lịch sử tìm kiếm</div>';
          }
        } else {
          alert("Lỗi: Không thể xóa. Vui lòng thử lại.");
        }
      });

      ul.appendChild(li);
    });

    dd.innerHTML = ""; // Xóa nội dung "Đang tải..."
    dd.appendChild(ul);

    // *** THÊM MỚI: Nút "Xóa tất cả" ***
    const clearBtn = document.createElement("div");
    clearBtn.className = "clear-all-hist";
    clearBtn.textContent = "Xóa tất cả lịch sử";
    clearBtn.addEventListener("click", async function () {
      if (confirm("Bạn có chắc muốn xóa TẤT CẢ lịch sử tìm kiếm?")) {
        if (await clearAllHistory()) {
          dd.innerHTML =
            '<div style="padding:10px;color:#666">Chưa có lịch sử tìm kiếm</div>';
        } else {
          alert("Lỗi: Không thể xóa. Vui lòng thử lại.");
        }
      }
    });
    dd.appendChild(clearBtn);
  }

  function hideDropdown() {
    dd.style.display = "none";
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[c];
    });
  }

  // show dropdown on focus or when clicking search icon
  input.addEventListener("focus", showDropdown);
  input.addEventListener("input", function () {
    if (input.value.trim() === "") showDropdown();
    else dd.style.display = "none";
  });
  window.addEventListener("resize", positionDropdown);
  window.addEventListener("scroll", function () {
    if (dd.style.display === "block") positionDropdown();
  });

  // click outside to hide
  document.addEventListener("click", function (e) {
    if (!dd.contains(e.target) && e.target !== input) hideDropdown();
  });
})();
