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
                    <img src="" alt="商品圖片" class="product-image">
                </div>

                <div class="product-details">
                    <div class="product-name">
                        <?php //echo htmlspecialchars($product['name']); 
                        ?>
                    </div>
                    <div class="product-price">
                        NT$ <?php //echo number_format($product['price']); 
                            ?>
                    </div>
                </div>
            </div>
            <div class="review-section">
                <div class="review-header">商品評論</div>
                <button class="write-review-button" onclick="openReviewModal()">撰寫評論</button>

                <div class="review-content">
                    <div class="average-rating">
                        <div class="rating-number"><?php //echo $average_rating; 
                                                    ?></div>
                        <div class="empty-stars" style="color: #ffc107;">
                            <?php //echo renderStars($average_rating); 
                            ?>
                        </div>
                    </div>

                    <div class="rating-breakdown">
                        <?php
                        for ($star = 5; $star >= 1; $star--) {
                            $count = $star_counts[$star];
                            $percentage = ($total_reviews > 0) ? ($count / $total_reviews) * 100 : 0;
                        ?>
                            <div class="rating-row">
                                <span class="rating-stars">
                                    <?php //echo renderStars($star); 
                                    ?>
                                </span>
                                <div class="rating-bar-wrapper">
                                    <div class="rating-bar" style="width: <?php //echo $percentage; 
                                                                            ?>%;"></div>
                                </div>
                                <span class="rating-count"><?php //echo $count; 
                                                            ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="all-reviews-container">
                <h3>全部評論 (共 <?php //echo $total_reviews; 
                            ?> 則)</h3>

                <?php //if (empty($all_reviews)): 
                ?>
                <div class="single-review" style="text-align: center; color: #999;">目前尚無評論。</div>
                <?php //else: 
                ?>
                <?php //foreach ($all_reviews as $review): 
                ?>
                <div class="single-review">
                    <div class="review-user-info">
                        <span class="review-user-id"><?php //echo htmlspecialchars($review['user_id']); 
                                                        ?></span>
                        <span class="review-stars">
                            <?php //echo renderStars($review['rating']); 
                            ?>
                        </span>
                    </div>
                    <div class="review-comment">
                        評語：<?php //echo nl2br(htmlspecialchars($review['comment'])); 
                            ?>
                    </div>
                </div>
                <?php //endforeach; 
                ?>
                <?php //endif; 
                ?>
            </div>
        </div>
        <div id="reviewModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title">撰寫評論</div>
                    <span class="modal-close" onclick="closeReviewModal()">&times;</span>
                </div>

                <form action="#" method="POST">
                    <label class="modal-label">您的評分:</label>
                    <input type="hidden" name="rating" id="ratingInput" value="5">

                    <div class="modal-rating-stars">
                        <span data-value="1">★</span>
                        <span data-value="2">★</span>
                        <span data-value="3">★</span>
                        <span data-value="4">★</span>
                        <span data-value="5">★</span>
                    </div>

                    <label class="modal-label">您的心得:</label>
                    <textarea class="modal-textarea" name="comment" placeholder="請輸入評語"></textarea>

                    <button type="submit" class="modal-submit-button">提交評論</button>
                </form>
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
                goHome() {
                    location.href = "index.php";
                },
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
                toProducts(id) {

                    location.href = "product_details.php?product_id=" + id;
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