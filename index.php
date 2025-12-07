<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
  <meta charset="UTF-8" />
  <title>屈臣氏 Watsons — 官方線上購物平台 Demo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Vue 3 & Axios -->
  <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <style>
    body {
      background: #f6f6f6;
      font-family: "Noto Sans TC", sans-serif;
    }

    .watsons-header {
      background: #00a0af;
      color: white;
      padding: 12px 20px;
    }

    .logo-text {
      font-size: 24px;
      font-weight: bold;
    }

    .product-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .cart-drawer {
      position: fixed;
      top: 0;
      right: 0;
      width: 350px;
      height: 100%;
      background: white;
      box-shadow: -3px 0 10px rgba(0, 0, 0, 0.2);
      padding: 20px;
      overflow-y: auto;
    }

    .product-name {
      white-space: nowrap;
      /* 單行顯示 */
      overflow: hidden;
      /* 超出部分隱藏 */
      text-overflow: ellipsis;
      /* 顯示省略號 */
      max-width: 200px;
      /* 或根據卡片寬度調整 */
    }

    .product-img {
      width: 100%;
      height: 180px;
      /* 你想限制的高度 */
      object-fit: contain;
      /* ⬅️ 保持原比例，不變形 */
      background-color: #fff;
      /* Watsons 商品圖常見白底 */
    }
  </style>
</head>

<body>
  <div id="app">

    <!-- HEADER -->
    <div class="watsons-header d-flex justify-content-between align-items-center">
      <div class="logo-text">Watsons 屈臣氏</div>

      <div>
        <button class="btn btn-light me-2" v-if="!user" @click="openModal('login')">登入</button>
        <button class="btn btn-outline-light me-2" v-if="!user" @click="openModal('register')">註冊</button>

        <button v-if="user" class="btn btn-outline-light me-2" onclick="location.href='information.php'">Hi, {{ user.name }}</button>
        <button class="btn btn-warning" @click="toggleCart">🛒 購物車 {{ cartCount }}</button>
      </div>
    </div>
    <div class="row">

      <!-- 左側分類導覽列 -->
      <div class="col-3 col-md-2 border-end bg-light vh-100 p-3">

        <h5 class="fw-bold mb-3" align="center">商品分類</h5>

        <ul class="list-group">
          <li
            class="list-group-item"
            :class="{'active': selectedCategory === ''}"
            @click="selectedCategory = ''"
            style="cursor:pointer">
            全部商品
          </li>

          <li v-for="c in categories"
            :key="c.category_id"
            class="list-group-item"
            :class="{'active': selectedCategory === c.category_id}"
            @click="selectedCategory = c.category_id"
            style="cursor:pointer">
            {{ c.category_name }}
          </li>
        </ul>

      </div>

      <!-- 右側商品列表 -->
      <div class="col-9 col-md-10 p-4">

        <h3 class="mb-4" align="center">商品列表</h3>
        <!-- 搜尋框 -->
        <div class="mb-4 d-flex justify-content-center">
          <input type="text" class="form-control w-50" placeholder="輸入商品名稱搜尋" v-model="searchQuery">
        </div>
        <div class="row g-3">

          <div class="col-6 col-md-4 col-lg-3" v-for="p in filteredProducts" :key="p.product_id" @click="toProducts(p.product_id)">
            <div class="card product-card shadow-sm border-0">
              <img :src="p.picture" class="card-img-top product-img">
              <div class="card-body">
                <h6 class="card-title fw-bold product-name" :title=" p.product_name">{{ p.product_name }}</h6>
                <p class="text-danger fw-bold">$ {{ p.price }}</p>
                <button class="btn btn-primary w-100" @click="addToCart(p)">加入購物車</button>
              </div>
            </div>
          </div>

        </div>
        <div class="pagination mt-4 d-flex align-items-center justify-content-center gap-1 flex-wrap">
          <button class="btn btn-outline-secondary btn-sm" @click="goPage(1)" :disabled="currentPage===1">第一頁</button>
          <button class="btn btn-outline-secondary btn-sm" @click="goPage(currentPage-1)" :disabled="currentPage===1">上一頁</button>

          <button v-for="n in pageNumbers" :key="n" class="btn btn-sm"
            :class="n===currentPage ? 'btn-primary' : 'btn-outline-primary'"
            @click="goPage(n)">
            {{ n }}
          </button>

          <button class="btn btn-outline-secondary btn-sm" @click="goPage(currentPage+1)" :disabled="currentPage===totalPages">下一頁</button>
          <button class="btn btn-outline-secondary btn-sm" @click="goPage(totalPages)" :disabled="currentPage===totalPages">最後一頁</button>

          <!-- 前往頁碼輸入 -->
          <form @submit="goToInputPage" class="d-flex align-items-center ms-3">
            <input type="number" v-model.number="inputPage" min="1" :max="totalPages" class="form-control form-control-sm" style="width:70px;">
            <button type="submit" class="btn btn-sm btn-secondary ms-1">Go</button>
          </form>
        </div>
      </div>



    </div>


    <!-- CART DRAWER -->
    <div v-if="cartOpen" class="cart-drawer">
      <h4 class="fw-bold">🛍 購物車</h4>
      <hr>

      <div v-for="item in cart" class="mb-3">
        <h6>{{ item.name }}</h6>
        <p class="text-danger">$ {{ item.price }}</p>

        <div class="d-flex align-items-center">
          <button class="btn btn-sm btn-secondary" @click="changeQty(item,-1)" :disabled="item.qty<=1">-</button>
          <span class="px-3">{{ item.qty }}</span>
          <button class="btn btn-sm btn-secondary" @click="changeQty(item,+1)">+</button>
        </div>
        <hr>
      </div>

      <h5 class="fw-bold">總金額：$ {{ total }}</h5>
      <button class="btn btn-success w-100 mt-3" @click="checkout">結帳</button>
      <button class="btn btn-outline-dark w-100 mt-2" @click="toggleCart">關閉</button>
    </div>


    <!-- MODAL -->
    <div class="modal fade" id="authModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ modalTitle }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            <!-- LOGIN -->
            <div v-if="mode==='login'">
              <input class="form-control mb-3" v-model="loginForm.email" placeholder="Email">
              <input class="form-control mb-3" type="password" v-model="loginForm.password" placeholder="密碼">
              <button class="btn btn-primary w-100" @click="login">登入</button>
              <button class="btn btn-link mt-2" @click="openModal('forgot')">忘記密碼？</button>
            </div>

            <!-- REGISTER -->
            <div v-if="mode==='register'">
              <input class="form-control mb-3" v-model="registerForm.username" placeholder="帳號">
              <input class="form-control mb-3" v-model="registerForm.email" placeholder="Email">
              <input class="form-control mb-3" type="password" v-model="registerForm.password" placeholder="密碼">
              <button class="btn btn-success w-100" @click="register">建立帳號</button>
            </div>

            <!-- FORGOT -->
            <div v-if="mode==='forgot'">
              <input class="form-control mb-3" v-model="forgotForm.email" placeholder="輸入 Email 重設密碼">
              <button class="btn btn-warning w-100" @click="forgotPassword">寄送重設連結</button>
            </div>

          </div>

        </div>
      </div>
    </div>

  </div>


  <script>
    const {
      createApp
    } = Vue;

    createApp({
      data() {
        return {
          user: null,
          cartOpen: false,
          products: [],
          cart: [],
          searchQuery: '', // 搜尋欄位

          mode: "login",
          loginForm: {
            email: "",
            password: ""
          },
          registerForm: {
            username: "",
            email: "",
            password: ""
          },
          forgotForm: {
            email: ""
          },

          selectedCategory: "",
          categories: [],

          currentPage: 1,
          pageSize: 20,
          inputPage: 1
        }
      },

      computed: {
        modalTitle() {
          return this.mode === "login" ? "會員登入" :
            this.mode === "register" ? "註冊新帳號" :
            "忘記密碼";
        },
        cartCount() {
          return this.cart.reduce((a, b) => a + b.qty, 0)
        },
        total() {
          return this.cart.reduce((t, i) => t + i.qty * i.price, 0)
        },


        // 篩選後的商品 (分類 + 搜尋)
        filteredProductsFull() {
          let prods = this.products;

          // 篩選分類
          if (this.selectedCategory) {
            prods = prods.filter(p => p.category_id === this.selectedCategory);
          }

          // 篩選搜尋關鍵字
          if (this.searchQuery.trim() !== '') {
            const query = this.searchQuery.trim().toLowerCase();
            prods = prods.filter(p => p.product_name.toLowerCase().includes(query));
          }

          return prods;
        },

        // 分頁後的商品
        filteredProducts() {
          const start = (this.currentPage - 1) * this.pageSize;
          const end = start + this.pageSize;
          return this.filteredProductsFull.slice(start, end);
        },

        totalPages() {
          return Math.ceil(this.filteredProductsFull.length / this.pageSize);
        },
        pageNumbers() {
          let start_page = Math.max(1, this.currentPage - 5);
          let end_page = Math.min(this.totalPages, this.currentPage + 4);

          if (this.totalPages <= 10) {
            start_page = 1;
            end_page = this.totalPages;
          } else if (end_page - start_page < 9) {
            if (start_page === 1) {
              end_page = Math.min(this.totalPages, start_page + 9);
            } else {
              start_page = Math.max(1, end_page - 9);
            }
          }

          let pages = [];
          for (let i = start_page; i <= end_page; i++) pages.push(i);
          return pages;
        }
      },

      methods: {
        modalTitle() {
          return this.mode === "login" ? "會員登入" :
            this.mode === "register" ? "註冊新帳號" :
            "忘記密碼";
        },
        /*** 開啟 modal ***/
        openModal(mode) {
          this.mode = mode;
          new bootstrap.Modal(document.getElementById('authModal')).show();
        },

        toggleCart() {
          this.cartOpen = !this.cartOpen;
        },

        /*** 加入購物車 ***/
        addToCart(p) {
          if (this.user == null) {
            this.openModal('login');
          } else {
            let f = this.cart.find(x => x.product_id === p.product_id);
            if (f) f.qty++;
            else this.cart.push({
              ...p,
              qty: 1
            });
          }

        },
        goPage(page) {
          if (page < 1) page = 1;
          if (page > this.totalPages) page = this.totalPages;
          this.currentPage = page;
          this.inputPage = page;
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        },
        goToInputPage(event) {
          event.preventDefault();
          const pageInput = parseInt(this.inputPage);
          if (!isNaN(pageInput)) this.goPage(pageInput);
        },
        changeQty(item, d) {
          item.qty += d;
        },

        /*** Auth ***/
        login() {
          axios.post("api.php?action=login", this.loginForm).then(res => {
            if (res.data.success) {
              this.user = res.data.user;
              bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
            } else alert("帳號或密碼錯誤");
          });
        },

        register() {
          axios.post("api.php?action=register", this.registerForm).then(res => {
            alert(res.data.msg);
            if (res.data.success) this.openModal('login');
          });
        },

        forgotPassword() {
          axios.post("api.php?action=forgot", this.forgotForm).then(res => {
            alert(res.data.message);
          });
        },

        checkout() {
          axios.post("api.php?action=checkout", {
            cart: this.cart,
            total: this.total
          }).then(res => {
            if (res.data.success) {
              alert("訂單完成！");
              this.cart = [];
            } else alert(res.data.msg);
          })
        },
toProducts(id){

  location.href = "comment.php?product_id="+id;
}


      },

      mounted() {
        axios.get("api.php?action=session").then(res => {
          if (res.data.logged) this.user = res.data.user;
        });
        axios.get("api.php?action=products").then(res => this.products = res.data);
        axios.get("api.php?action=categories").then(res => this.categories = res.data);

      },
      watch: {
        // 當分類或搜尋文字改變時，自動回到第 1 頁
        selectedCategory() {
          this.currentPage = 1;
          this.inputPage = 1;
        },
        searchQuery() {
          this.currentPage = 1;
          this.inputPage = 1;
        }
      }
    }).mount("#app");
  </script>

</body>

</html>