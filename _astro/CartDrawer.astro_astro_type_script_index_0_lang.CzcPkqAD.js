var e=[],t=document.getElementById(`cart-overlay`),n=document.getElementById(`cart-drawer`),r=document.getElementById(`open-cart-btn`),i=document.getElementById(`close-cart-btn`),a=document.querySelector(`.continue-shopping-btn`),o=document.getElementById(`cart-items-container`),s=document.getElementById(`cart-total-value`),c=document.getElementById(`global-cart-badge`),l=document.getElementById(`checkout-inquiry-btn`);function u(){n?.classList.add(`open`),t?.classList.add(`visible`),document.body.style.overflow=`hidden`}function d(){n?.classList.remove(`open`),t?.classList.remove(`visible`),document.body.style.overflow=``}r?.addEventListener(`click`,u),i?.addEventListener(`click`,d),t?.addEventListener(`click`,d),a?.addEventListener(`click`,d);function f(){let t=localStorage.getItem(`astroyu-cart`);if(t)try{e=JSON.parse(t)}catch{e=[]}m()}function p(){localStorage.setItem(`astroyu-cart`,JSON.stringify(e)),m()}function m(){let t=e.reduce((e,t)=>e+t.quantity,0);if(c&&(c.textContent=t.toString(),c.style.display=t>0?`flex`:`none`),!(!o||!s||!l||!n))if(e.length===0)o.innerHTML=`
				<div class="empty-cart-state">
					<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
					<p>ยังไม่มีสินค้าในตะกร้า</p>
					<button class="btn btn-secondary btn-sm continue-shopping-btn" id="empty-continue-btn">เลือกซื้อสินค้า</button>
				</div>
			`,document.getElementById(`empty-continue-btn`)?.addEventListener(`click`,d),s.textContent=`฿0`,document.getElementById(`cart-footer`)?.classList.remove(`visible`);else{document.getElementById(`cart-footer`)?.classList.add(`visible`);let t=`<div class="cart-items-list">`,n=0;e.forEach(e=>{let r=e.price*e.quantity;n+=r,t+=`
					<div class="cart-item">
						<div class="cart-item-img">
							<img src="${e.image}" alt="${e.name}" />
						</div>
						<div class="cart-item-details">
							<h4 class="cart-item-title">${e.name}</h4>
							<div class="cart-item-price">฿${e.price.toLocaleString(`th-TH`)}</div>
							<div class="cart-item-controls">
								<div class="quantity-picker">
									<button class="qty-btn dec-qty" data-id="${e.id}">-</button>
									<span class="qty-val">${e.quantity}</span>
									<button class="qty-btn inc-qty" data-id="${e.id}">+</button>
								</div>
								<button class="remove-item-btn" data-id="${e.id}" aria-label="Remove item">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
								</button>
							</div>
						</div>
					</div>
				`}),t+=`</div>`,o.innerHTML=t,s.textContent=`฿${n.toLocaleString(`th-TH`)}`,document.querySelectorAll(`.dec-qty`).forEach(e=>{e.addEventListener(`click`,e=>{let t=e.currentTarget.getAttribute(`data-id`);t&&g(t,-1)})}),document.querySelectorAll(`.inc-qty`).forEach(e=>{e.addEventListener(`click`,e=>{let t=e.currentTarget.getAttribute(`data-id`);t&&g(t,1)})}),document.querySelectorAll(`.remove-item-btn`).forEach(e=>{e.addEventListener(`click`,e=>{let t=e.currentTarget.getAttribute(`data-id`);t&&_(t)})})}}function h(t,n,r,i){let a=e.find(e=>e.id===t);a?a.quantity+=1:e.push({id:t,name:n,price:r,image:i,quantity:1}),p(),u()}function g(t,n){let r=e.find(e=>e.id===t);r&&(r.quantity+=n,r.quantity<=0?_(t):p())}function _(t){e=e.filter(e=>e.id!==t),p()}document.addEventListener(`click`,e=>{let t=e.target.closest(`.add-to-cart-btn`);if(t){let e=t.getAttribute(`data-id`)||``,n=t.getAttribute(`data-name`)||``,r=parseFloat(t.getAttribute(`data-price`)||`0`),i=t.getAttribute(`data-image`)||``;e&&n&&h(e,n,r,i)}}),l?.addEventListener(`click`,()=>{if(e.length===0)return;let t=`สวัสดีครับ สนใจสั่งซื้อสินค้าดังต่อไปนี้:
`;e.forEach((e,n)=>{t+=`${n+1}. ${e.name} (จำนวน ${e.quantity} ชิ้น) - ฿${(e.price*e.quantity).toLocaleString(`th-TH`)}\n`});let n=e.reduce((e,t)=>e+t.price*t.quantity,0);t+=`ยอดรวมทั้งหมด: ฿${n.toLocaleString(`th-TH`)}\n\nรบกวนติดต่อกลับและขอใบเสนอราคาด้วยครับ`,d();let r=document.getElementById(`contact-message`),i=document.getElementById(`contact-subject`);r&&(r.value=t,r.dispatchEvent(new Event(`input`,{bubbles:!0}))),i&&(i.value=`สั่งซื้อสินค้า / ขอใบเสนอราคา`,i.dispatchEvent(new Event(`input`,{bubbles:!0})));let a=document.getElementById(`contact`);if(a){a.scrollIntoView({behavior:`smooth`});let e=a.querySelector(`form`);e?.classList.add(`highlight-pulse`),setTimeout(()=>{e?.classList.remove(`highlight-pulse`)},2e3)}}),document.addEventListener(`DOMContentLoaded`,()=>{f()});