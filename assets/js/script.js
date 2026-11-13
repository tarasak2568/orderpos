// ============================================
// ระบบ POS ร้านอาหาร - JavaScript หลัก
// ============================================

// ============================================
// Toast Notifications
// ============================================
function showToast(message, type = "success") {
  const container = document.querySelector(".toast-container");
  if (!container) {
    const div = document.createElement("div");
    div.className = "toast-container";
    document.body.appendChild(div);
  }

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<i class="fas ${type === "success" ? "fa-check-circle" : type === "error" ? "fa-times-circle" : type === "warning" ? "fa-exclamation-triangle" : "fa-info-circle"}"></i> ${message}`;

  document.querySelector(".toast-container").appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateX(100%)";
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ============================================
// Modal
// ============================================
function openModal(modalId) {
  document.getElementById(modalId).classList.add("active");
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

// Close modal on overlay click
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal-overlay")) {
    e.target.classList.remove("active");
  }
});

// ============================================
// API Helper
// ============================================
const API = {
  baseUrl: "/api",

  async request(endpoint, method = "GET", data = null) {
    const options = {
      method,
      headers: {
        "Content-Type": "application/json",
      },
    };

    if (data) {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(`${this.baseUrl}/${endpoint}`, options);
      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || "เกิดข้อผิดพลาด");
      }

      return result;
    } catch (error) {
      showToast(error.message, "error");
      throw error;
    }
  },

  get(endpoint) {
    return this.request(endpoint, "GET");
  },

  post(endpoint, data) {
    return this.request(endpoint, "POST", data);
  },

  put(endpoint, data) {
    return this.request(endpoint, "PUT", data);
  },

  delete(endpoint) {
    return this.request(endpoint, "DELETE");
  },
};

// ============================================
// Format Currency
// ============================================
function formatCurrency(amount) {
  return (
    "฿" +
    parseFloat(amount).toLocaleString("th-TH", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

// ============================================
// Format Date/Time
// ============================================
function formatDateTime(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleString("th-TH", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatTime(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleTimeString("th-TH", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// ============================================
// Shared Member CRM Checkout Picker (POS & Billing)
// ============================================
class MemberCheckoutPicker {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    this.onTotalsChange =
      typeof options.onTotalsChange === "function"
        ? options.onTotalsChange
        : () => {};
    this.config = {
      member_point_rate: parseInt(options.config?.member_point_rate ?? 25, 10) || 0,
      member_redeem_value:
        parseFloat(options.config?.member_redeem_value ?? 1.0) || 0,
    };
    this.grossAmount = 0;
    this.selectedMember = null;
    this.pointsToRedeem = 0;
    this.searchResults = [];
    this.showQuickRegister = false;
    this.render();
  }

  reset(grossAmount = 0) {
    this.grossAmount = Math.max(0, parseFloat(grossAmount) || 0);
    this.selectedMember = null;
    this.pointsToRedeem = 0;
    this.searchResults = [];
    this.showQuickRegister = false;
    this.render();
    this.notifyTotals();
  }

  setGrossAmount(grossAmount) {
    this.grossAmount = Math.max(0, parseFloat(grossAmount) || 0);
    this.render();
    this.notifyTotals();
  }

  calculateTotals() {
    const gross = Math.max(0, Math.round(this.grossAmount * 100) / 100);
    const pointRate = Math.max(0, parseInt(this.config.member_point_rate, 10) || 0);
    const redeemValue = Math.max(
      0,
      parseFloat(this.config.member_redeem_value) || 0,
    );

    if (!this.selectedMember) {
      return {
        gross_amount: gross,
        tier_discount: 0,
        points_redeemed: 0,
        point_discount: 0,
        discount_amount: 0,
        net_amount: gross,
        points_earned: 0,
      };
    }

    const discPct = Math.min(
      100,
      Math.max(0, parseFloat(this.selectedMember.discount_percent) || 0),
    );
    const tierDiscount = Math.round(gross * (discPct / 100) * 100) / 100;
    const afterTier = Math.max(0, Math.round((gross - tierDiscount) * 100) / 100);

    const availablePts = Math.max(0, parseInt(this.selectedMember.points, 10) || 0);
    const wantedPts = Math.max(0, parseInt(this.pointsToRedeem, 10) || 0);
    let usedPts = 0;
    let pointDiscount = 0;
    if (redeemValue > 0 && availablePts > 0 && wantedPts > 0 && afterTier > 0) {
      const maxPtsByTotal = Math.floor(afterTier / redeemValue);
      usedPts = Math.min(wantedPts, availablePts, maxPtsByTotal);
      pointDiscount = Math.round(usedPts * redeemValue * 100) / 100;
    }

    const totalDiscount = Math.min(
      gross,
      Math.round((tierDiscount + pointDiscount) * 100) / 100,
    );
    const netAmount = Math.max(
      0,
      Math.round((gross - totalDiscount) * 100) / 100,
    );
    const pointsEarned =
      pointRate > 0 ? Math.floor(netAmount / pointRate) : 0;

    return {
      gross_amount: gross,
      tier_discount: tierDiscount,
      points_redeemed: usedPts,
      point_discount: pointDiscount,
      discount_amount: totalDiscount,
      net_amount: netAmount,
      points_earned: pointsEarned,
    };
  }

  getSelection() {
    const totals = this.calculateTotals();
    return {
      member_id: this.selectedMember ? this.selectedMember.id : 0,
      points_redeemed: totals.points_redeemed,
      totals,
    };
  }

  notifyTotals() {
    this.onTotalsChange(this.calculateTotals());
  }

  async lookup(queryStr) {
    const q = (queryStr || "").trim();
    if (!q) {
      this.searchResults = [];
      this.render();
      return;
    }
    try {
      const res = await API.get(
        `members.php?action=lookup&q=${encodeURIComponent(q)}`,
      );
      if (res.loyalty_config) {
        this.config = res.loyalty_config;
      }
      this.searchResults = res.data || [];
      if (
        this.searchResults.length === 1 &&
        this.searchResults[0].phone === q.replace(/[\s\-().]+/g, "")
      ) {
        this.selectMember(this.searchResults[0]);
      } else {
        this.render();
      }
    } catch (e) {
      // handled by API
    }
  }

  selectMember(member) {
    this.selectedMember = member;
    this.pointsToRedeem = 0;
    this.searchResults = [];
    this.showQuickRegister = false;
    this.render();
    this.notifyTotals();
  }

  clearMember() {
    this.selectedMember = null;
    this.pointsToRedeem = 0;
    this.searchResults = [];
    this.render();
    this.notifyTotals();
  }

  async quickRegister(fullName, phone) {
    if (!fullName.trim() || phone.trim().length < 8) {
      showToast("กรุณาระบุชื่อและเบอร์โทรสมาชิกอย่างน้อย 8 หลัก", "warning");
      return;
    }
    try {
      const res = await API.post("members.php", {
        full_name: fullName.trim(),
        phone: phone.trim(),
        tier: "bronze",
      });
      showToast("สมัครสมาชิกใหม่เรียบร้อยแล้ว!", "success");
      if (res.data) {
        this.selectMember(res.data);
      }
    } catch (e) {
      // handled
    }
  }

  render() {
    if (!this.container) return;
    const totals = this.calculateTotals();
    const idPrefix = this.container.id;

    if (this.selectedMember) {
      const m = this.selectedMember;
      const tierInfo = m.tier_info || {
        label: m.tier || "Member",
        bg: "#FFF3ED",
        color: "#C2410C",
      };
      const redeemDisabled =
        this.config.member_redeem_value <= 0 || m.points <= 0;

      this.container.innerHTML = `
        <div style="background:#FFF7ED;border:1.5px solid #FDBA74;border-radius:10px;padding:12px;margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px;">
            <div>
              <strong style="color:#9A3412;"><i class="fas fa-address-card"></i> ${escapeHtml(m.full_name)}</strong>
              <span style="display:inline-block;margin-left:6px;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:700;background:${escapeHtml(tierInfo.bg)};color:${escapeHtml(tierInfo.color)};">${escapeHtml(tierInfo.label)}</span>
              <div style="font-size:12px;color:#7C2D12;">โทร: ${escapeHtml(m.phone)} · แต้มสะสม: <strong>${Number(m.points).toLocaleString()}</strong> แต้ม${m.discount_percent > 0 ? ` · ส่วนลด ${m.discount_percent}%` : ""}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-mbr-clear="1" style="padding:4px 10px;">เปลี่ยน</button>
          </div>
          ${!redeemDisabled ? `
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px;padding-top:8px;border-top:1px dashed #FDBA74;">
            <label for="${idPrefix}_pts" style="font-size:12.5px;margin:0;color:#7C2D12;">แลกแต้มส่วนลด (1 แต้ม = ${formatCurrency(this.config.member_redeem_value)}):</label>
            <input type="number" id="${idPrefix}_pts" min="0" max="${m.points}" value="${totals.points_redeemed}" style="width:90px;padding:5px 8px;border:1px solid #FDBA74;border-radius:6px;font-family:inherit;font-size:13px;">
          </div>` : ""}
          <div style="display:flex;justify-content:space-between;font-size:12.5px;color:#065F46;margin-top:8px;padding-top:8px;border-top:1px dashed #FDBA74;">
            <span>ส่วนลดรวม: <strong>-${formatCurrency(totals.discount_amount)}</strong></span>
            <span>หลังชำระจะได้รับเพิ่ม: <strong>+${totals.points_earned} แต้ม</strong></span>
          </div>
        </div>
      `;

      const clearBtn = this.container.querySelector("[data-mbr-clear]");
      if (clearBtn) {
        clearBtn.addEventListener("click", () => this.clearMember());
      }
      const ptsInput = document.getElementById(`${idPrefix}_pts`);
      if (ptsInput) {
        ptsInput.addEventListener("input", (e) => {
          this.pointsToRedeem = Math.max(0, parseInt(e.target.value, 10) || 0);
          const nextTotals = this.calculateTotals();
          const summaryDiv = ptsInput.parentElement?.nextElementSibling;
          if (summaryDiv) {
            summaryDiv.innerHTML = `
              <span>ส่วนลดรวม: <strong>-${formatCurrency(nextTotals.discount_amount)}</strong></span>
              <span>หลังชำระจะได้รับเพิ่ม: <strong>+${nextTotals.points_earned} แต้ม</strong></span>
            `;
          }
          this.notifyTotals();
        });
      }
      return;
    }

    this.container.innerHTML = `
      <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px;margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <span style="font-size:13px;font-weight:600;color:#334155;"><i class="fas fa-address-card" style="color:var(--primary);"></i> สมาชิกลูกค้า / สะสมแต้ม (ไม่บังคับ)</span>
          <button type="button" data-mbr-toggle-reg="1" style="background:none;border:none;color:var(--primary);font-family:inherit;font-size:12.5px;font-weight:600;cursor:pointer;">
            ${this.showQuickRegister ? "ปิดฟอร์มสมัคร" : "+ สมัครสมาชิกด่วน"}
          </button>
        </div>
        <div style="display:flex;gap:6px;">
          <input type="text" id="${idPrefix}_search" placeholder="ใส่เบอร์โทร หรือชื่อสมาชิก..." style="flex:1;padding:8px 10px;border:1.5px solid #CBD5E1;border-radius:8px;font-family:inherit;font-size:13px;">
          <button type="button" class="btn btn-sm btn-outline" data-mbr-search-btn="1"><i class="fas fa-search"></i> ค้นหา</button>
        </div>
        ${this.searchResults.length > 0 ? `
          <div style="display:flex;flex-direction:column;gap:5px;margin-top:8px;max-height:140px;overflow-y:auto;">
            ${this.searchResults.map((m, idx) => `
              <button type="button" data-mbr-pick="${idx}" style="text-align:left;padding:7px 10px;border:1px solid #CBD5E1;border-radius:7px;background:#FFF;cursor:pointer;font-family:inherit;font-size:12.5px;display:flex;justify-content:space-between;align-items:center;">
                <span><strong>${escapeHtml(m.full_name)}</strong> (${escapeHtml(m.phone)})</span>
                <span style="color:#FF6B35;font-weight:600;">${Number(m.points).toLocaleString()} แต้ม</span>
              </button>
            `).join("")}
          </div>
        ` : ""}
        ${this.showQuickRegister ? `
          <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #CBD5E1;display:grid;grid-template-columns:1fr 1fr;gap:6px;">
            <input type="text" id="${idPrefix}_reg_name" placeholder="ชื่อลูกค้า *" style="padding:7px 9px;border:1px solid #CBD5E1;border-radius:6px;font-family:inherit;font-size:12.5px;">
            <input type="tel" id="${idPrefix}_reg_phone" placeholder="เบอร์โทร *" style="padding:7px 9px;border:1px solid #CBD5E1;border-radius:6px;font-family:inherit;font-size:12.5px;">
            <button type="button" class="btn btn-sm btn-primary" data-mbr-reg-save="1" style="grid-column:1/-1;">บันทึกสมาชิก & ใช้สิทธิ์ทันที</button>
          </div>
        ` : ""}
      </div>
    `;

    const searchBtn = this.container.querySelector("[data-mbr-search-btn]");
    const searchInput = document.getElementById(`${idPrefix}_search`);
    if (searchBtn && searchInput) {
      searchBtn.addEventListener("click", () => this.lookup(searchInput.value));
      searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          this.lookup(searchInput.value);
        }
      });
    }
    const toggleReg = this.container.querySelector("[data-mbr-toggle-reg]");
    if (toggleReg) {
      toggleReg.addEventListener("click", () => {
        this.showQuickRegister = !this.showQuickRegister;
        this.render();
      });
    }
    this.container.querySelectorAll("[data-mbr-pick]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const idx = parseInt(btn.getAttribute("data-mbr-pick"), 10);
        if (this.searchResults[idx]) {
          this.selectMember(this.searchResults[idx]);
        }
      });
    });
    const saveRegBtn = this.container.querySelector("[data-mbr-reg-save]");
    if (saveRegBtn) {
      saveRegBtn.addEventListener("click", () => {
        const n = document.getElementById(`${idPrefix}_reg_name`)?.value || "";
        const p = document.getElementById(`${idPrefix}_reg_phone`)?.value || "";
        this.quickRegister(n, p);
      });
    }
  }
}

// ============================================
// Customer Menu Page
// ============================================
class CustomerMenu {
  constructor() {
    this.cart = [];
    this.currentCategory = "all";
    this.menuItems = [];
    this.categories = [];
    this.shopId = this.getShopId();
    this.tableId = this.getTableId();

    this.init();
  }

  getShopId() {
    if (window.ORDERPOS_SHOP && window.ORDERPOS_SHOP.shopId) {
      return window.ORDERPOS_SHOP.shopId;
    }
    const pageEl = document.querySelector(".menu-page");
    if (pageEl && pageEl.dataset.shopId) {
      return parseInt(pageEl.dataset.shopId, 10) || 1;
    }
    const params = new URLSearchParams(window.location.search);
    return parseInt(params.get("shop_id"), 10) || 1;
  }

  getTableId() {
    if (window.ORDERPOS_SHOP && window.ORDERPOS_SHOP.tableId) {
      return window.ORDERPOS_SHOP.tableId;
    }
    const params = new URLSearchParams(window.location.search);
    return params.get("table_id") || params.get("table") || "T01";
  }

  async init() {
    await this.loadData();
    this.renderCategories();
    this.renderMenu();
    this.renderCart();
    this.bindEvents();
  }

  async loadData() {
    try {
      const [menuData, catData] = await Promise.all([
        API.get(`menu_items.php?shop_id=${this.shopId}`),
        API.get(`categories.php?shop_id=${this.shopId}`),
      ]);
      this.menuItems = menuData.data || [];
      this.categories = catData.data || [];
    } catch (error) {
      console.error("Load data error:", error);
    }
  }

  renderCategories() {
    const container = document.querySelector(".menu-categories");
    if (!container) return;

    let html = `<button class="category-btn active" data-category="all">ทั้งหมด</button>`;

    this.categories.forEach((cat) => {
      html += `<button class="category-btn" data-category="${cat.id}">${cat.name}</button>`;
    });

    container.innerHTML = html;
  }

  renderMenu() {
    const container = document.querySelector(".menu-items");
    if (!container) return;

    let items = this.menuItems;
    if (this.currentCategory !== "all") {
      items = items.filter((item) => item.category_id == this.currentCategory);
    }

    if (items.length === 0) {
      container.innerHTML =
        '<div class="text-center text-gray" style="padding: 40px;"><i class="fas fa-utensils" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>ไม่มีเมนูในหมวดนี้</div>';
      return;
    }

    let html = "";
    items.forEach((item) => {
      const inCart = this.cart.find((c) => c.id === item.id);
      const qty = inCart ? inCart.qty : 0;

      html += `
                <div class="menu-item" data-id="${item.id}" onclick="customerMenu.addToCart(${item.id})">
                    <div class="item-image" style="position: relative;">
                        ${item.image ? `<img src="uploads/${item.image}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;">` : `<i class="fas fa-utensils"></i>`}
                        ${item.is_recommended ? '<span class="item-badge">แนะนำ</span>' : ""}
                        ${qty > 0 ? `<span style="position:absolute;bottom:5px;right:5px;background:var(--primary);color:white;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">${qty}</span>` : ""}
                    </div>
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">${formatCurrency(item.price)}</div>
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  }

  addToCart(itemId) {
    const item = this.menuItems.find((i) => i.id === itemId);
    if (!item) return;

    const existing = this.cart.find((c) => c.id === itemId);
    if (existing) {
      existing.qty++;
    } else {
      this.cart.push({
        id: item.id,
        name: item.name,
        price: parseFloat(item.price),
        qty: 1,
      });
    }

    this.renderCart();
    this.renderMenu();
  }

  removeFromCart(itemId) {
    const existing = this.cart.find((c) => c.id === itemId);
    if (existing) {
      if (existing.qty > 1) {
        existing.qty--;
      } else {
        this.cart = this.cart.filter((c) => c.id !== itemId);
      }
    }

    this.renderCart();
    this.renderMenu();
  }

  renderCart() {
    const countEl = document.querySelector(".cart-count");
    const totalEl = document.querySelector(".cart-total");
    const itemsEl = document.querySelector(".cart-items");
    const footerTotalEl = document.querySelector(
      ".cart-sidebar-footer .total-row span:last-child",
    );

    const totalQty = this.cart.reduce((sum, item) => sum + item.qty, 0);
    const totalPrice = this.cart.reduce(
      (sum, item) => sum + item.price * item.qty,
      0,
    );

    if (countEl) countEl.textContent = totalQty;
    if (totalEl) totalEl.textContent = formatCurrency(totalPrice);
    if (footerTotalEl) footerTotalEl.textContent = formatCurrency(totalPrice);

    if (itemsEl) {
      if (this.cart.length === 0) {
        itemsEl.innerHTML =
          '<div class="text-center text-gray" style="padding: 30px;"><i class="fas fa-shopping-cart" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>ยังไม่มีรายการ</div>';
      } else {
        let html = "";
        this.cart.forEach((item) => {
          html += `
                        <div class="cart-item">
                            <div class="item-details">
                                <div class="item-name">${item.name}</div>
                                <div class="item-price">${formatCurrency(item.price)}</div>
                            </div>
                            <div class="qty-control">
                                <button onclick="customerMenu.removeFromCart(${item.id})">−</button>
                                <span>${item.qty}</span>
                                <button onclick="customerMenu.addToCart(${item.id})">+</button>
                            </div>
                        </div>
                    `;
        });
        itemsEl.innerHTML = html;
      }
    }
  }

  toggleCart() {
    document.querySelector(".cart-sidebar").classList.toggle("open");
  }

  async submitOrder() {
    if (this.cart.length === 0) {
      showToast("กรุณาเลือกรายการอาหารก่อน", "warning");
      return;
    }

    const note = document.getElementById("orderNote")?.value || "";

    try {
      const result = await API.post(`orders.php?action=create&shop_id=${this.shopId}`, {
        shop_id: this.shopId,
        table_id: this.tableId,
        items: this.cart.map((item) => ({
          menu_item_id: item.id,
          quantity: item.qty,
          price: item.price,
        })),
        note: note,
      });

      showToast("ส่งออเดอร์เรียบร้อยแล้ว!", "success");
      this.cart = [];
      this.renderCart();
      this.renderMenu();
      this.toggleCart();

      if (document.getElementById("orderNote")) {
        document.getElementById("orderNote").value = "";
      }
    } catch (error) {
      showToast("ไม่สามารถส่งออเดอร์ได้ กรุณาลองใหม่", "error");
    }
  }

  bindEvents() {
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("category-btn")) {
        document
          .querySelectorAll(".category-btn")
          .forEach((btn) => btn.classList.remove("active"));
        e.target.classList.add("active");
        this.currentCategory = e.target.dataset.category;
        this.renderMenu();
      }
    });
  }
}

// ============================================
// Staff POS Page (รองรับทั้งร้านอาหารและร้านของชำ/ค้าปลีก)
// ============================================
class StaffPOS {
  constructor() {
    this.cart = [];
    this.currentCategory = "all";
    this.searchQuery = "";
    this.menuItems = [];
    this.categories = [];
    this.tables = [];
    this.shopType =
      window.ORDERPOS_POS_CONFIG?.shopType ||
      document.querySelector(".pos-layout")?.dataset?.shopType ||
      "restaurant";

    this.init();
  }

  async init() {
    await this.loadData();
    this.renderCategories();
    this.renderMenu();
    this.renderTables();
    this.renderCart();
    this.bindEvents();
  }

  async loadData() {
    try {
      const [menuData, catData, tableData] = await Promise.all([
        API.get("menu_items.php"),
        API.get("categories.php"),
        API.get("tables.php"),
      ]);
      this.menuItems = menuData.data || [];
      this.categories = catData.data || [];
      this.tables = tableData.data || [];
    } catch (error) {
      console.error("Load data error:", error);
    }
  }

  renderCategories() {
    const container = document.querySelector(".pos-categories");
    if (!container) return;

    let html = `<button class="active" data-category="all">ทั้งหมด</button>`;
    this.categories.forEach((cat) => {
      html += `<button data-category="${cat.id}">${cat.name}</button>`;
    });
    container.innerHTML = html;
  }

  renderMenu() {
    const container = document.querySelector(".pos-items");
    if (!container) return;

    let items = this.menuItems;
    if (this.currentCategory !== "all") {
      items = items.filter((item) => item.category_id == this.currentCategory);
    }
    if (this.searchQuery !== "") {
      const q = this.searchQuery.toLowerCase();
      items = items.filter(
        (item) =>
          (item.name && item.name.toLowerCase().includes(q)) ||
          (item.sku && item.sku.toLowerCase().includes(q)),
      );
    }

    if (items.length === 0) {
      container.innerHTML =
        '<div class="text-center text-gray" style="grid-column:1/-1;padding:40px;">ไม่พบรายการที่ค้นหา</div>';
      return;
    }

    let html = "";
    items.forEach((item) => {
      const hasStockLimit =
        item.stock_qty !== null && item.stock_qty !== undefined;
      const outOfStock = hasStockLimit && item.stock_qty <= 0;
      const stockText = hasStockLimit
        ? `<div style="font-size:11px;color:${outOfStock ? "#DC2626" : "#64748B"};margin-top:2px;">${outOfStock ? "สินค้าหมด" : `คงเหลือ ${item.stock_qty}`}</div>`
        : "";

      html += `
                <div class="pos-item" style="${outOfStock ? "opacity:0.5;cursor:not-allowed;" : ""}" onclick="staffPos.addToCart(${item.id})">
                    <div class="item-icon"><i class="fas ${this.shopType === "retail" ? "fa-box-open" : "fa-utensils"}"></i></div>
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">${formatCurrency(item.price)}</div>
                    ${stockText}
                </div>
            `;
    });
    container.innerHTML = html;
  }

  renderTables() {
    const select = document.querySelector(".pos-cart-header select");
    if (!select) return;

    const isRetail = this.shopType === "retail";
    let html = isRetail
      ? '<option value="WALKIN">🛒 ขายหน้าร้าน (Walk-in)</option>'
      : '<option value="WALKIN">🛍️ ขายหน้าร้าน / สั่งกลับบ้าน (Walk-in)</option>';

    this.tables.forEach((table) => {
      if (table.table_code === "WALKIN" && isRetail) return;
      html += `<option value="${table.table_code}">${table.table_name} (${table.status === "available" ? "ว่าง" : "ไม่ว่าง"})</option>`;
    });
    select.innerHTML = html;
  }

  addToCart(itemId) {
    const item = this.menuItems.find((i) => i.id === itemId);
    if (!item) return;

    const hasStockLimit =
      item.stock_qty !== null && item.stock_qty !== undefined;
    const existing = this.cart.find((c) => c.id === itemId);
    const currentQty = existing ? existing.qty : 0;

    if (hasStockLimit && currentQty + 1 > item.stock_qty) {
      showToast(`สต๊อก "${item.name}" เหลือเพียง ${item.stock_qty} ชิ้น`, "warning");
      return;
    }

    if (existing) {
      existing.qty++;
    } else {
      this.cart.push({
        id: item.id,
        name: item.name,
        price: parseFloat(item.price),
        qty: 1,
      });
    }

    this.renderCart();
  }

  removeFromCart(itemId) {
    const existing = this.cart.find((c) => c.id === itemId);
    if (existing) {
      if (existing.qty > 1) {
        existing.qty--;
      } else {
        this.cart = this.cart.filter((c) => c.id !== itemId);
      }
    }
    this.renderCart();
  }

  getTotalPrice() {
    return this.cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  }

  renderCart() {
    const itemsEl = document.querySelector(".pos-cart-items");
    const totalEl = document.querySelector(
      ".pos-cart-footer .total-row span:last-child",
    );
    const countEl = document.querySelector(".pos-cart-footer .total-row small");

    const totalPrice = this.getTotalPrice();
    const totalQty = this.cart.reduce((sum, item) => sum + item.qty, 0);

    if (totalEl) totalEl.textContent = formatCurrency(totalPrice);
    if (countEl) countEl.textContent = `(${totalQty} รายการ)`;

    if (itemsEl) {
      if (this.cart.length === 0) {
        itemsEl.innerHTML =
          '<div class="text-center text-gray" style="padding: 30px;"><i class="fas fa-shopping-cart" style="font-size: 36px; margin-bottom: 10px; display: block;"></i>ยังไม่มีรายการ</div>';
      } else {
        let html = "";
        this.cart.forEach((item) => {
          html += `
                        <div class="pos-cart-item">
                            <div class="item-info">
                                <div class="item-name">${item.name}</div>
                                <div class="item-subtotal">${formatCurrency(item.price * item.qty)}</div>
                            </div>
                            <div class="qty-control">
                                <button onclick="staffPos.removeFromCart(${item.id})">−</button>
                                <span>${item.qty}</span>
                                <button onclick="staffPos.addToCart(${item.id})">+</button>
                            </div>
                        </div>
                    `;
        });
        itemsEl.innerHTML = html;
      }
    }
  }

  async submitOrder() {
    const tableSelect = document.querySelector(".pos-cart-header select");
    const tableId = tableSelect?.value || "WALKIN";

    if (this.cart.length === 0) {
      showToast("กรุณาเลือกรายการก่อน", "warning");
      return;
    }

    try {
      await API.post("orders.php?action=create", {
        table_id: tableId,
        items: this.cart.map((item) => ({
          menu_item_id: item.id,
          quantity: item.qty,
          price: item.price,
        })),
      });

      showToast("ส่งออเดอร์เข้าครัวเรียบร้อย", "success");
      this.cart = [];
      this.renderCart();
      await this.loadData();
      this.renderMenu();
    } catch (error) {
      showToast("ไม่สามารถสร้างออเดอร์ได้", "error");
    }
  }

  getNetCheckoutTotal() {
    if (this.memberPicker) {
      return this.memberPicker.calculateTotals().net_amount;
    }
    return this.getTotalPrice();
  }

  openInstantCheckout() {
    if (this.cart.length === 0) {
      showToast("กรุณาเลือกสินค้าเข้าตะกร้าก่อนคิดเงิน", "warning");
      return;
    }
    const gross = this.getTotalPrice();
    this._cashTouched = false;
    if (!this.memberPicker && document.getElementById("posMemberBox")) {
      this.memberPicker = new MemberCheckoutPicker("posMemberBox", {
        config: window.ORDERPOS_POS_CONFIG?.loyalty || {},
        onTotalsChange: (totals) => {
          const totalEl = document.getElementById("posPayTotalDisplay");
          if (totalEl) totalEl.textContent = formatCurrency(totals.net_amount);
          const cashEl = document.getElementById("posCashReceived");
          if (cashEl && !this._cashTouched) {
            cashEl.value = totals.net_amount;
          }
          this.updateChangeDisplay();
        },
      });
    }
    if (this.memberPicker) {
      this.memberPicker.reset(gross);
    }
    const net = this.getNetCheckoutTotal();
    document.getElementById("posPayTotalDisplay").textContent =
      formatCurrency(net);
    document.getElementById("posPayMethod").value = "cash";
    document.getElementById("posCashReceived").value = net;
    this.toggleCashInput();
    this.updateChangeDisplay();
    openModal("posCheckoutModal");
  }

  toggleCashInput() {
    const method = document.getElementById("posPayMethod")?.value || "cash";
    const cashGroup = document.getElementById("posCashGroup");
    const changeBox = document.getElementById("posChangeBox");
    if (method === "cash") {
      if (cashGroup) cashGroup.style.display = "block";
      this.updateChangeDisplay();
    } else {
      if (cashGroup) cashGroup.style.display = "none";
      if (changeBox) changeBox.style.display = "none";
    }
  }

  setQuickCash(val) {
    const total = this.getNetCheckoutTotal();
    const input = document.getElementById("posCashReceived");
    if (!input) return;
    this._cashTouched = true;
    input.value = val === "exact" ? total : val;
    this.updateChangeDisplay();
  }

  updateChangeDisplay() {
    const total = this.getNetCheckoutTotal();
    const received =
      parseFloat(document.getElementById("posCashReceived")?.value) || 0;
    const changeBox = document.getElementById("posChangeBox");
    const changeEl = document.getElementById("posChangeAmount");
    if (!changeBox || !changeEl) return;

    if (received >= total && (total > 0 || received > 0)) {
      changeEl.textContent = formatCurrency(received - total);
      changeBox.style.display = "block";
    } else {
      changeBox.style.display = "none";
    }
  }

  async confirmInstantCheckout(event) {
    event.preventDefault();
    const total = this.getNetCheckoutTotal();
    const method = document.getElementById("posPayMethod")?.value || "cash";
    const cashReceived =
      parseFloat(document.getElementById("posCashReceived")?.value) || 0;
    const tableSelect = document.querySelector(".pos-cart-header select");
    const tableId = tableSelect?.value || "WALKIN";

    if (method === "cash" && cashReceived < total) {
      showToast("จำนวนเงินสดที่รับมาไม่เพียงพอ", "warning");
      return;
    }

    const selection = this.memberPicker
      ? this.memberPicker.getSelection()
      : { member_id: 0, points_redeemed: 0 };

    try {
      const result = await API.post("orders.php?action=create", {
        table_id: tableId,
        instant_pay: true,
        payment_method: method,
        cash_received: method === "cash" ? cashReceived : total,
        member_id: selection.member_id,
        points_redeemed: selection.points_redeemed,
        items: this.cart.map((item) => ({
          menu_item_id: item.id,
          quantity: item.qty,
          price: item.price,
        })),
      });

      const change = result.data?.change_amount || 0;
      const earned = parseInt(result.data?.points_earned || 0, 10);
      const ptsSuffix = earned > 0 ? ` (+${earned} แต้ม)` : "";
      showToast(
        (method === "cash"
          ? `รับชำระเรียบร้อย! เงินทอน ${formatCurrency(change)}`
          : "รับชำระเงินเรียบร้อย!") + ptsSuffix,
        "success",
      );
      closeModal("posCheckoutModal");
      this.cart = [];
      this.renderCart();
      await this.loadData();
      this.renderMenu();
    } catch (error) {
      showToast("ไม่สามารถบันทึกการชำระเงินได้", "error");
    }
  }

  bindEvents() {
    document.addEventListener("click", (e) => {
      if (e.target.closest(".pos-categories")) {
        const btn = e.target.closest("button");
        if (btn) {
          document
            .querySelectorAll(".pos-categories button")
            .forEach((b) => b.classList.remove("active"));
          btn.classList.add("active");
          this.currentCategory = btn.dataset.category;
          this.renderMenu();
        }
      }
    });

    const searchInput = document.getElementById("posSearchInput");
    if (searchInput) {
      searchInput.addEventListener("input", (e) => {
        this.searchQuery = e.target.value.trim();
        this.renderMenu();
      });

      // รองรับเครื่องยิงบาร์โค้ด (สแกนแล้วกด Enter หยิบใส่ตะกร้าทันที)
      searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          const code = searchInput.value.trim();
          if (!code) return;
          const exactSku = this.menuItems.find(
            (i) => i.sku && i.sku.toLowerCase() === code.toLowerCase(),
          );
          if (exactSku) {
            this.addToCart(exactSku.id);
            searchInput.value = "";
            this.searchQuery = "";
            this.renderMenu();
            showToast(`เพิ่ม "${exactSku.name}" ลงตะกร้าแล้ว`, "success");
          }
        }
      });
    }
  }
}

// ============================================
// Kitchen Display
// ============================================
class KitchenDisplay {
  constructor() {
    this.orders = [];
    this.init();
    this.startPolling();
  }

  async init() {
    await this.loadOrders();
  }

  async loadOrders() {
    try {
      const result = await API.get("orders.php?action=kitchen");
      this.orders = result.data || [];
      this.render();
    } catch (error) {
      console.error("Load orders error:", error);
    }
  }

  render() {
    const container = document.querySelector(".kitchen-grid");
    if (!container) return;

    if (this.orders.length === 0) {
      container.innerHTML =
        '<div class="text-center" style="grid-column:1/-1;padding:60px;"><i class="fas fa-check-circle" style="font-size:60px;color:var(--success);margin-bottom:15px;display:block;"></i><h2>ไม่มีออเดอร์ใหม่</h2></div>';
      return;
    }

    let html = "";
    this.orders.forEach((order) => {
      const statusClass = order.status;
      const items = JSON.parse(order.items || "[]");

      html += `
                <div class="kitchen-order ${statusClass}" data-order-id="${order.id}">
                    <div class="order-header">
                        <div class="order-table">${order.table_name}</div>
                        <div class="order-time">${formatTime(order.created_at)}</div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <span class="badge badge-${order.status}">${this.getStatusText(order.status)}</span>
                        <span style="color:var(--gray);font-size:13px;margin-left:10px;">#${order.order_no}</span>
                    </div>
                    <ul class="order-items">
                        ${items
                          .map(
                            (item) => `
                            <li>
                                <span><span class="qty">${item.quantity}x</span> ${item.name}</span>
                                ${item.note ? `<span style="color:var(--warning);font-size:12px;">${item.note}</span>` : ""}
                            </li>
                        `,
                          )
                          .join("")}
                    </ul>
                    <div class="order-actions">
                        ${order.status === "pending" ? `<button class="btn btn-info" onclick="kitchenDisplay.updateStatus(${order.id}, 'preparing')">รับออเดอร์</button>` : ""}
                        ${order.status === "preparing" ? `<button class="btn btn-success" onclick="kitchenDisplay.updateStatus(${order.id}, 'ready')">ทำเสร็จแล้ว</button>` : ""}
                        ${order.status === "ready" ? `<button class="btn btn-secondary" onclick="kitchenDisplay.updateStatus(${order.id}, 'served')">เสิร์ฟแล้ว</button>` : ""}
                    </div>
                </div>
            `;
    });

    container.innerHTML = html;
  }

  getStatusText(status) {
    const texts = {
      pending: "รอดำเนินการ",
      preparing: "กำลังทำ",
      ready: "ทำเสร็จ",
      served: "เสิร์ฟแล้ว",
    };
    return texts[status] || status;
  }

  async updateStatus(orderId, status) {
    try {
      await API.post("orders.php?action=status", {
        order_id: orderId,
        status: status,
      });
      showToast("อัปเดตสถานะเรียบร้อย", "success");
      await this.loadOrders();
    } catch (error) {
      showToast("ไม่สามารถอัปเดตสถานะได้", "error");
    }
  }

  startPolling() {
    setInterval(() => this.loadOrders(), 10000);
  }
}

// ============================================
// Initialize based on page
// ============================================
document.addEventListener("DOMContentLoaded", function () {
  // Customer Menu
  if (document.querySelector(".menu-page")) {
    window.customerMenu = new CustomerMenu();
  }

  // Staff POS
  if (document.querySelector(".pos-layout")) {
    window.staffPos = new StaffPOS();
  }

  // Kitchen Display
  if (document.querySelector(".kitchen-page")) {
    window.kitchenDisplay = new KitchenDisplay();
  }
});
