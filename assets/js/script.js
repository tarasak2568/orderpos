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

  openInstantCheckout() {
    if (this.cart.length === 0) {
      showToast("กรุณาเลือกสินค้าเข้าตะกร้าก่อนคิดเงิน", "warning");
      return;
    }
    const total = this.getTotalPrice();
    document.getElementById("posPayTotalDisplay").textContent =
      formatCurrency(total);
    document.getElementById("posPayMethod").value = "cash";
    document.getElementById("posCashReceived").value = total;
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
    const total = this.getTotalPrice();
    const input = document.getElementById("posCashReceived");
    if (!input) return;
    input.value = val === "exact" ? total : val;
    this.updateChangeDisplay();
  }

  updateChangeDisplay() {
    const total = this.getTotalPrice();
    const received =
      parseFloat(document.getElementById("posCashReceived")?.value) || 0;
    const changeBox = document.getElementById("posChangeBox");
    const changeEl = document.getElementById("posChangeAmount");
    if (!changeBox || !changeEl) return;

    if (received >= total && total > 0) {
      changeEl.textContent = formatCurrency(received - total);
      changeBox.style.display = "block";
    } else {
      changeBox.style.display = "none";
    }
  }

  async confirmInstantCheckout(event) {
    event.preventDefault();
    const total = this.getTotalPrice();
    const method = document.getElementById("posPayMethod")?.value || "cash";
    const cashReceived =
      parseFloat(document.getElementById("posCashReceived")?.value) || 0;
    const tableSelect = document.querySelector(".pos-cart-header select");
    const tableId = tableSelect?.value || "WALKIN";

    if (method === "cash" && cashReceived < total) {
      showToast("จำนวนเงินสดที่รับมาไม่เพียงพอ", "warning");
      return;
    }

    try {
      const result = await API.post("orders.php?action=create", {
        table_id: tableId,
        instant_pay: true,
        payment_method: method,
        cash_received: method === "cash" ? cashReceived : total,
        items: this.cart.map((item) => ({
          menu_item_id: item.id,
          quantity: item.qty,
          price: item.price,
        })),
      });

      const change = result.data?.change_amount || 0;
      showToast(
        method === "cash"
          ? `รับชำระเรียบร้อย! เงินทอน ${formatCurrency(change)}`
          : "รับชำระเงินเรียบร้อย!",
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
