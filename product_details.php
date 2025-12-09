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

        .page-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* 商品資訊區塊 */
        .product-main-info {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .product-image-wrapper {
            width: 250px;
            height: 250px;
            border: 1px solid #eee;
            overflow: hidden;
            flex-shrink: 0;
            background-color: #f0f0f0;
            /* 空白圖片的背景色 */
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .product-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .product-price {
            font-size: 22px;
            color: #c00;
            font-weight: bold;
        }

        /* 評論區塊 */
        .review-section {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 0;
            position: relative;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .review-header {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .review-content {
            display: flex;
            align-items: flex-start;
        }

        .average-rating {
            flex-shrink: 0;
            width: 150px;
            text-align: center;
        }

        .rating-number {
            font-size: 56px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .empty-stars {
            font-size: 24px;
            color: #ccc;
            letter-spacing: 2px;
        }

        .rating-breakdown {
            flex-grow: 1;
            padding-left: 30px;
        }

        .rating-row {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .rating-stars {
            width: 70px;
            color: #ffc107;
            flex-shrink: 0;
            font-size: 18px;
        }

        .rating-bar-wrapper {
            width: 800px;
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin: 0 30px;
            overflow: hidden;
        }

        .rating-bar {
            height: 100%;
            background-color: #ffc107;
            width: 0%;
            transition: width 0.5s ease;
        }

        .rating-count {
            width: 20px;
            text-align: right;
            color: #555;
            font-size: 16px;
        }

        .write-review-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #343a40;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .separator-line {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }

        .all-reviews-container {
            margin-top: 20px;
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .single-review {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .single-review:last-child {
            border-bottom: none;
        }

        .review-user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .review-user-id {
            font-weight: bold;
            color: #343a40;
            font-size: 16px;
        }

        .review-stars {
            color: #ffc107;
            font-size: 18px;
        }

        .review-comment {
            color: #555;
            line-height: 1.6;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: #343a40;
        }

        .modal-close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-label {
            font-weight: bold;
            color: #555;
            margin-top: 15px;
            margin-bottom: 8px;
            display: block;
        }

        .modal-rating-stars {
            font-size: 32px;
            color: #ffc107;
            cursor: pointer;
        }

        .modal-rating-stars span {
            margin-right: 5px;
        }

        .modal-textarea {
            width: 95%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
            box-sizing: border-box;
        }

        .modal-submit-button {
            width: 100%;
            background-color: #6495ed;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div id="app">

        <!-- HEADER -->
        <div class="watsons-header d-flex justify-content-between align-items-center">
            <div class="logo-text">Watsons 屈臣氏</div>

            <div>
                <button class="btn btn-light me-2" @click="goHome">← 返回首頁</button>
                <button class="btn btn-light me-2" v-if="!user" @click="openModal('login')">登入</button>
                <button class="btn btn-outline-light me-2" v-if="!user" @click="openModal('register')">註冊</button>

                <button v-if="user" class="btn btn-outline-light me-2" onclick="location.href='information.php'">Hi, {{ user.name }}</button>
                <button class="btn btn-warning" @click="toggleCart">🛒 購物車 {{ cartCount }}</button>
            </div>
        </div>

        <div class="page-content">
            <div class="product-main-info">
                <div class="product-image-wrapper">
                    <img :src="product.picture" alt="商品圖片" class="product-image" v-if="product">
                </div>

                <div class="product-details">
                    <div class="product-name">
                        {{ product ? product.product_name:"" }}
                    </div>
                    <p>庫存：{{ product ? product.stock:"" }}</p>
                    <div class="product-price">
                        NT$ {{ product ? product.price:"" }}
                    </div><br />
                    <button class="btn btn-primary w-100" @click="addToCart()" :disabled="!canOrder">{{canOrder ? '加入購物車' : '庫存不足' }}</button>
                </div>
            </div>
            <div class="review-section">
                <h4>商品評論</h4>

                <!-- 新增評論 -->
                <div class="mb-3">
                    <label>評分：</label>
                    <span v-for="r in 5" @click="setRating(r)"
                        :style="{cursor:'pointer', color: r <= newReview.rating ? '#ffc107' : '#ccc'}">★</span>
                </div>
                <textarea v-model="newReview.comment" placeholder="輸入您的評論" class="form-control mb-2"></textarea>
                <button class="btn btn-primary mb-3" @click="submitReview">提交評論</button>

                <!-- 評論列表 -->
                <div v-if="reviews.length === 0" style="color:#999;">目前尚無評論。</div>
                <div v-else>
                    <div v-for="r in reviews" :key="r._id" class="border-bottom py-2">
                        <div>
                            <strong>{{ r.customer_name }}</strong>
                            <span style="color:#ffc107">{{ '★'.repeat(r.rating) + '☆'.repeat(5-r.rating) }}</span>
                            <small class="text-muted">{{ new Date(r.created_at).toLocaleString() }}</small>
                        </div>
                        <div>{{ r.comment }}</div>
                    </div>
                </div>
            </div>
        </div>



        <!-- CART DRAWER -->
        <div v-if="cartOpen" class="cart-drawer">
            <h4 class="fw-bold">🛍 購物車</h4>
            <hr>

            <div v-for="item in cart" class="mb-3">
                <h6>{{ item.product_name }}</h6>
                <p class="text-danger">$ {{ item.price }}</p>

                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-secondary" @click="changeQty(item,-1)">-</button>
                    <span class="px-3">{{ item.qty }}</span>
                    <button class="btn btn-sm btn-secondary" @click="changeQty(item,+1)">+</button>
                </div>
                <hr>
            </div>

            <h5 class="fw-bold">總金額：$ {{ total }}</h5>
            <!-- 付款方式選擇 -->
            <div class="mb-3">
                <label class="form-label fw-bold">付款方式</label>
                <select v-model="payment_id" class="form-select">
                    <option value="1" selected>刷卡</option>
                    <option value="2">現金</option>
                </select>
            </div>
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
                            <button class="btn btn-link mt-2" @click="goAdminLogin">後台登入</button>
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
        let product_id = <?php echo $_GET["product_id"]; ?>;



        const {
            createApp
        } = Vue;

        createApp({
            data() {
                return {

                    user: null,
                    cartOpen: false,
                    product: null,
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
                    inputPage: 1,

                    reviews: [], // 評論
                    newReview: {
                        rating: 5,
                        comment: ''
                    },

                    stock: 0, // 庫存
                    canOrder: true, // 是否可下單
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



            },

            methods: {
                goHome() {
                    location.href = "index.php";
                },
                modalTitle() {
                    return this.mode === "login" ? "會員登入" :
                        this.mode === "register" ? "註冊新帳號" :
                        "忘記密碼";
                },
                goAdminLogin() {
                    location.href = "backstage/admin_login.php";
                },
                /*** 開啟 modal ***/
                openModal(mode) {
                    if (mode == "forgot") {
                        const modalEl = document.getElementById('authModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl); // 取得已存在的 Modal 實例
                        if (modalInstance) modalInstance.hide(); // 關閉 Modal
                    }
                    this.mode = mode;

                    new bootstrap.Modal(document.getElementById('authModal')).show();
                },

                toggleCart() {
                    this.cartOpen = !this.cartOpen;
                },

                /*** 加入購物車 ***/
                addToCart() {
                    if (this.user == null) {
                        this.openModal('login');
                    } else {
                        axios.post('api.php?action=addToCart', {
                            product_id: product_id,
                            qty: 1
                        }).then(res => {
                            if (res.data.success) {
                                alert('已加入購物車');
                                this.cart = res.data.cart; // 更新前端購物車顯示
                                // this.loadCart(); // 載入購物車
                            } else {
                                alert(res.data.msg || '加入購物車失敗');
                            }
                        });
                    }

                },
                loadCart() {
                    // 進入頁面時載入 SESSION 購物車
                    axios.get('api.php?action=getCart').then(res => {
                        if (res.data.success) {
                            this.cart = res.data.cart;
                        }
                    });
                },

                changeQty(item, d) {
                    let newQty = item.qty + d;
                    // if (newQty < 1) return;

                    axios.post('api.php?action=updateCart', {
                        product_id: item.product_id,
                        qty: newQty
                    }).then(res => {
                        if (res.data.success) {
                            this.cart = res.data.cart;
                        }
                    });
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
                    if (this.cart.length === 0) {
                        alert("購物車為空");
                        return;
                    }
                    if (!this.user) {
                        this.openModal('login');
                        return;
                    }
                    axios.post("api.php?action=checkout", {
                        cart: this.cart,
                        total: this.total,
                        payment_id: this.payment_id,
                    }).then(res => {
                        if (res.data.success) {
                            alert(`訂單完成！訂單編號：${res.data.order_id}`);
                            this.cart = [];
                            this.toggleCart();
                        } else {
                            alert(`結帳失敗：${res.data.msg}`);
                        }
                    }).catch(err => {
                        console.error(err);
                        alert("結帳時發生錯誤");
                    });
                },

                fetchReviews() {
                    if (!product_id) return;
                    // console.log(`http://localhost:3001/reviews/${product_id}`);
                    axios.get(`http://localhost:3001/reviews/${product_id}`)
                        .then(res => {
                            if (res.data.success) this.reviews = res.data.reviews;
                        })
                        .catch(err => console.error(err));
                },

                submitReview() {
                    if (!this.user) {
                        this.openModal('login');
                        return;
                    }

                    if (!this.newReview.comment.trim()) {
                        alert('請輸入評論內容');
                        return;
                    }

                    axios.post('http://localhost:3001/reviews', {
                        product_id: this.product.product_id,
                        customer_id: this.user.id, // 或 user.email
                        rating: this.newReview.rating,
                        comment: this.newReview.comment
                    }).then(res => {
                        if (res.data.success) {
                            this.newReview.comment = '';
                            this.newReview.rating = 5;
                            this.fetchReviews(); // 重新載入評論
                        } else {

                            alert(res.data.msg);
                        }
                    });
                },

                setRating(r) {
                    this.newReview.rating = r;
                },

                async fetchStock() {
                    try {
                        const res = await axios.get(`http://localhost:3001/stock/${product_id}`);
                        this.stock = res.data.stock;
                        this.canOrder = this.stock > 0;
                    } catch (err) {
                        console.error('庫存抓取失敗', err);
                        this.stock = 0;
                        this.canOrder = false;
                    }
                },

                addToCart() {
                    if (!this.canOrder) {
                        alert('庫存不足，無法下單');
                        return;
                    }
                    if (!this.user) {
                        this.openModal('login');
                        return;
                    }

                    axios.post('api.php?action=addToCart', {
                            product_id,
                            qty: 1
                        })
                        .then(res => {
                            if (res.data.success) {
                                alert('已加入購物車');
                                this.cart = res.data.cart
                            } else alert(res.data.msg || '加入購物車失敗');
                        });
                },
            },

            mounted() {

                axios.get("api.php?action=session").then(res => {
                    if (res.data.logged) this.user = res.data.user;
                });
                axios.post("api.php?action=logproducts", {
                    product_id: product_id
                }).then(res => {
                    // console.log(res)
                    if (res.data.success) {
                        this.product = res.data.product;
                        // bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
                    }
                });
                // axios.get("api.php?action=products").then(res => this.products = res.data);
                axios.get("api.php?action=categories").then(res => this.categories = res.data);
                this.loadCart(); // 載入購物車
                this.fetchReviews(); // 進入頁面時載入 MongoDB 評論
                // this.fetchStock(); // 載入庫存
                // 每 10 秒自動刷新庫存
                // this.stockInterval = setInterval(() => {
                //     this.fetchStock();
                // }, 10000); // 10 秒
            },
            beforeUnmount() {
                // 清除定時器，避免離開頁面後繼續抓庫存
                if (this.stockInterval) clearInterval(this.stockInterval);
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