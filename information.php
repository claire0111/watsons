<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8" />
    <title>屈臣氏 Watsons — 基本資料</title>
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

        /* 藍色標頭樣式 */
        .watsons-header {
            background: #00a0af;
            color: white;
            padding: 12px 20px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
        }

        /* 紅色星號樣式 */
        .required-star {
            color: red;
            margin-right: 5px;
        }

        /* 內容容器樣式 */
        .registration-container {
            max-width: 800px;
            /* 限制表單寬度 */
        }

        /* 表單列樣式 */
        .form-row-item {
            display: grid;
            grid-template-columns: 100px 1fr;
            /* 標題固定寬度，輸入框佔滿剩餘空間 */
            gap: 20px;
            align-items: center;
        }

        /* 地址排版樣式 */
        .form-row-address .col-12 {
            padding-left: 6px;
            /* 與左側標題對齊 */
        }
    </style>
</head>

<body>
    <div id="app">
        <div class="watsons-header d-flex justify-content-between align-items-center">
            <div class="logo-text">Watsons 屈臣氏</div>

            <div>
                <button class="btn btn-light me-2" @click="goHome">← 返回首頁</button>
                <button class="btn btn-warning me-2" @click="logout">登出</button>
                <!-- <button class="btn btn-warning" @click="toggleCart">🛒 購物車 {{ cartCount }}</button> -->
                <!-- <button v-if="user" class="btn btn-outline-light me-2" onclick="location.href='information.php'">Hi, {{ user.name }}</button> -->
            </div>
        </div>

        <div class="container py-5 registration-container">
            <h3 class="mb-4 fw-bold text-center">基本資料</h3>
            <!-- 會員卡 + 進度條區塊 -->
            <div id="memberApp" class="container py-4">

                <!-- 會員卡資訊 -->
                <div class="card mb-4 shadow-sm p-3 d-flex flex-row align-items-center gap-3">

                    <!-- 卡片圖片 -->
                    <div>
                        <img :src="cardImage" alt="會員卡" style="width:120px;">
                    </div>

                    <!-- 會員文字資訊 -->
                    <div class="flex-grow-1">
                        <h4 class="mb-1">{{ profile.name }} 的會員卡</h4>
                        <p class="mb-1">等級：<strong>{{ profile.membership_level_id==1?"銀卡":profile.membership_level_id==2?"金卡":"白金卡"  }}</strong></p>
                        <p class="mb-1">目前點數：<strong>{{ profile.points }}</strong> 點</p>
                        <small class="text-muted">{{ levelDescription }}</small>
                    </div>
                </div>

                <!-- 升級進度條 -->
                <div class="card p-3 shadow-sm">
                    <h5 class="fw-bold mb-3">升級進度</h5>

                    <div class="mb-2 d-flex justify-content-between">
                        <span>{{ profile.membership_level_id==1?"銀卡":profile.membership_level_id==2?"金卡":"白金卡" }} → {{ nextLevel }}</span>
                        <span v-if="nextLevel !== '已達最高等級'">
                            還差 <strong>{{ pointsToNext }}</strong> 點
                        </span>
                    </div>

                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped bg-success"
                            role="progressbar"
                            :style="{ width: progressPercent + '%' }">
                            {{ Math.floor(progressPercent) }}%
                        </div>
                    </div>

                    <p class="text-muted mt-2" v-if="nextLevel === '已達最高等級'">
                        🎉 您已達最高等級白金卡，享有最高回饋！
                    </p>
                </div>

            </div>

            <div class="card p-4 shadow-sm">
                <div class="mb-3 form-row-item">
                    <label class="form-label text-end">
                        <span class="required-star">*</span>姓名
                    </label>
                    <input v-if="user" type="text" class="form-control" :value="profile.name">
                </div>

                <div class="mb-3 form-row-item">
                    <label class="form-label text-end">
                        <span class="required-star">*</span>電子信箱
                    </label>
                    <input v-if="user" type="email" class="form-control" :value="profile.email">
                </div>

                <div class="mb-3 form-row-item">
                    <label class="form-label text-end">
                        <span class="required-star">*</span>手機號碼
                    </label>
                    <input type="tel" class="form-control" v-if="user" :value="profile.phone">
                </div>

                <div class="mb-3 form-row-item">
                    <label class="form-label text-end">
                        <span class="required-star">*</span>地址
                    </label>
                    <div class="d-flex align-items-center flex-wrap">
                        <label class="me-2">縣市</label>
                        <select class="form-select me-3" style="width: 130px;" v-model="profile.city" @change="updateDistricts">
                            <option disabled>請選擇縣市</option>
                            <option v-for="(city, index) in cities" :key="index" :value="city.city">{{ city.city }}</option>
                        </select>

                        <label class="me-2">區</label>
                        <select class="form-select me-3" style="width: 120px;" v-model="profile.district" @change="updateZipCode" :disabled="!profile.city">
                            <option value="" disabled>請選擇區</option>
                            <option v-for="(district, index) in districts" :key="index" :value="district.district">{{ district.district }}</option>
                        </select>

                        <label class="me-2">郵遞區號</label>
                        <input type="text" class="form-control" style="width: 80px;" :value="profile.postal_code" disabled>
                    </div>
                </div>

                <div class="mb-3 form-row-item form-row-address">
                    <label class="form-label text-end">
                        <span class="required-star">*</span>地址
                    </label>
                    <div class="col-12">
                        <input type="text" class="form-control" placeholder="請輸入主要地址" v-model="profile.address_line1" :value="profile.address_line1">
                    </div>
                </div>

                <div class="mb-4 form-row-item form-row-address">
                    <label class="form-label text-end">地址 (非必填)</label>
                    <div class="col-12">
                        <input type="text" class="form-control" placeholder="請輸入備用地址（非必填）" v-model="profile.address_line2" :value="profile.address_line2">
                    </div>
                </div>

                <div class="text-center">
                    <button class="btn btn-success btn-lg" style="width:200px" @click="updateProfile">送出</button>
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
                    cities: [],
                    districts: [],
                    user: null,
                    profile: {
                        name: "",
                        email: "",
                        phone: "",
                        city: "",
                        district: "",
                        postal_code: "",
                        address_line1: "",
                        address_line2: "",
                        membership_level_id: "",
                        points: ""
                    },
                    levelDescription: '' // 初始值
                }
            },
            computed: {
                cardImage() {
                    console.log(this.profile.membership_level_id)
                    // 根據等級回傳對應圖片
                    switch (this.profile.membership_level_id) {
                        case 1:
                            return "src/銀卡.png";
                        case 2:
                            return "src/金卡.png";
                        case 3:
                            return "src/白金卡.png";
                        default:
                            return "src/銀卡.png";
                    }
                },
                levelDescription() {
                    // console.log(this.profile.membership_level_id)
                    // 顯示每個等級的條件
                    switch (this.profile.membership_level_id) {
                        case 1:
                            return "銀卡條件：累積消費 0 - 2999 點數";
                        case 2:
                            return "金卡條件：累積消費 3000 - 6999 點數";
                        case 3:
                            return "白金卡條件：累積消費 7000 點以上";
                        default:
                            return "";
                    }
                },
                /* 下一個等級 */
                nextLevel() {
                    if (this.profile.membership_level_id === 1) return "金卡";
                    if (this.profile.membership_level_id === 2) return "白金卡";
                    return "已達最高等級";
                },

                /* 升級點數的範圍 */
                levelRanges() {
                    return {
                        1: {
                            min: 0,
                            max: 3000
                        },
                        2: {
                            min: 3000,
                            max: 7000
                        },
                        3: {
                            min: 7000,
                            max: 7000
                        } // 封頂
                    };
                },

                /* 距離下一級還差多少點 */
                pointsToNext() {
                    if (this.profile.membership_level_id === "白金卡") return 0;
                    const nextMax = this.levelRanges[this.profile.membership_level_id].max;
                    return Math.max(0, nextMax - this.profile.points);
                },

                /* 百分比進度條（0–100%） */
                progressPercent() {
                    const range = this.levelRanges[this.profile.membership_level_id] || {
                        min: 0,
                        max: 1
                    };
                    if (this.profile.membership_level_id === "白金卡") return 100;

                    const gained = this.profile.points - range.min;
                    const total = range.max - range.min;

                    return Math.min(100, Math.max(0, (gained / total) * 100));
                }
            },
            methods: {
                logout() {
                    axios.post("api.php?action=logout")
                        .then(res => {
                            alert("登出成功");
                            location.href = "index.php";
                        });
                },
                goHome() {
                    location.href = "index.php";
                },
                // 當選擇縣市時，更新區的下拉選單
                updateDistricts() {
                    this.profile.district = ''; // 重置區
                    this.profile.postal_code = ''; // 重置郵遞區號

                    const cityData = this.cities.find(c => c.city === this.profile.city);
                    // console.log(this.profile.city);
                    this.districts = cityData ? cityData.districts : [];
                },
                // 當選擇區時，自動帶入郵遞區號
                updateZipCode() {
                    if (this.profile.city && this.profile.district) {
                        const cityData = this.cities.find(c => c.city === this.profile.city);
                        if (cityData) {
                            const districtData = cityData.districts.find(d => d.district === this.profile.district);
                            this.profile.postal_code = districtData ? districtData.zip : '';
                        }
                    }
                },
                updateProfile() {
                    if (!this.validateForm()) return;

                    axios.post("api.php?action=updateProfile", this.profile)
                        .then(res => {
                            // console.log(res)
                            alert("資料已更新！");
                        });
                },
                /** 🔍表單欄位檢查 */
                validateForm() {
                    if (this.profile.name == undefined) return alert("請輸入姓名");
                    if (this.profile.email == undefined) return alert("請輸入電子信箱");
                    if (this.profile.phone == undefined) return alert("請輸入手機號碼");
                    if (this.profile.city == undefined) return alert("請選擇縣市");
                    if (this.profile.district == undefined) return alert("請選擇區");
                    if (this.profile.address_line1 == undefined) return alert("請輸入主要地址");

                    return true;
                },


            },
            mounted() {
                this.levelDescription = '銀卡：累積 0~1999 點';
                // 取得登入者
                axios.get("api.php?action=session").then(res => {
                    if (res.data.logged) {
                        this.user = res.data.user;
                    }
                });

                // 取得個人資料
                axios.get("api.php?action=getProfile").then(res => {
                    if (res.data.success) {
                        this.profile = res.data.profile;
                        if (this.profile.city != undefined) {
                            const cityData = this.cities.find(c => c.city === this.profile.city);
                            console.log(this.profile.city);
                            this.districts = cityData ? cityData.districts : [];
                        }
                    }
                });
                axios.get("taiwan_adderss_data.json")
                    .then(res => {
                        this.cities = res.data;
                    })
                    .catch(err => {
                        console.error("地址資料讀取失敗：", err);
                    });
            }
        }).mount('#app')
    </script>
</body>

</html>