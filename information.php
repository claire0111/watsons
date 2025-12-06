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
                <button class="btn btn-light me-2" @click="$router.push('/')">← 返回首頁</button>
                <button class="btn btn-warning" @click="toggleCart">🛒 購物車 {{ cartCount }}</button>
                <!-- <button v-if="user" class="btn btn-outline-light me-2" onclick="location.href='information.php'">Hi, {{ user.name }}</button> -->
            </div>
        </div>

        <div class="container py-5 registration-container">
            <h3 class="mb-4 fw-bold text-center">基本資料</h3>

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
                            <option value="" disabled>請選擇縣市</option>
                            <option v-for="(city, index) in cities" :key="index" :value="city.city">{{ city.city }}</option>
                        </select>

                        <label class="me-2">區</label>
                        <select class="form-select me-3" style="width: 120px;" v-model="selectedDistrict" @change="updateZipCode" :disabled="!selectedCity">
                            <option value="" disabled>請選擇區</option>
                            <option v-for="(district, index) in districts" :key="index" :value="district.district">{{ district.district }}</option>
                        </select>

                        <label class="me-2">郵遞區號</label>
                        <input type="text" class="form-control" style="width: 80px;" :value="zipCode" disabled>
                    </div>
                </div>

                <div class="mb-3 form-row-item form-row-address">
                    <label class="form-label text-end">
                        <span class="required-star">*</span>地址
                    </label>
                    <div class="col-12">
                        <input type="text" class="form-control" placeholder="請輸入主要地址" :value="profile.address_line1">
                    </div>
                </div>

                <div class="mb-4 form-row-item form-row-address">
                    <label class="form-label text-end">地址 (非必填)</label>
                    <div class="col-12">
                        <input type="text" class="form-control" placeholder="請輸入備用地址（非必填）" :value="profile.address_line2">
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
                    // selectedCity: '',
                    districts: [],
                    // selectedDistrict: '',
                    zipCode: '',
                    user: null,
                    profile: {
                        name: "",
                        email: "",
                        phone: "",
                        city: "",
                        district: "",
                        postal_code: "",
                        address_line1: "",
                        address_line2: ""
                    }
                }
            },
            methods: {
                goHome() {
                    location.href = "index.php";
                },
                // 當選擇縣市時，更新區的下拉選單
                updateDistricts() {
                    this.selectedDistrict = ''; // 重置區
                    this.zipCode = ''; // 重置郵遞區號

                    const cityData = this.cities.find(c => c.city === this.selectedCity);
                    this.districts = cityData ? cityData.districts : [];
                },
                // 當選擇區時，自動帶入郵遞區號
                updateZipCode() {
                    if (this.selectedCity && this.selectedDistrict) {
                        const cityData = this.cities.find(c => c.city === this.selectedCity);
                        if (cityData) {
                            const districtData = cityData.districts.find(d => d.district === this.selectedDistrict);
                            this.zipCode = districtData ? districtData.zip : '';
                        }
                    }
                },
                updateProfile() {
                    if (!this.validateForm()) return;

                    axios.post("api.php?action=updateProfile", this.profile)
                        .then(res => {
                            alert("資料已更新！");
                        });
                },
                /** 🔍表單欄位檢查 */
                validateForm() {
                    if (!this.user.name.trim() || this.user.name == undefined) return alert("請輸入姓名");
                    if (!this.user.email.trim() || this.user.name == undefined) return alert("請輸入電子信箱");
                    if (!this.user.Phone.trim() || this.user.name == undefined) return alert("請輸入手機號碼");
                    if (!this.selectedCity) return alert("請選擇縣市");
                    if (!this.selectedDistrict) return alert("請選擇區");
                    if (!this.user.address_line1.trim() || this.user.name == undefined) return alert("請輸入主要地址");

                    return true;
                },


            },
            mounted() {
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