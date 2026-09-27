<script setup>
import '../../../css/app.css'
import { ref } from 'vue'

const email = ref('')
const password = ref('')
</script>

<template>
    <main class="accounting-shell" dir="rtl">
        <section class="login-card">
            <div class="login-brand">
                <div class="brand-content">
                    <span class="brand-mark">ح</span>
                    <h1 class="brand-title">حسابداری صنعتی،<br>ساده و دقیق.</h1>
                    <p class="brand-text">مدیریت خرید، انبار، تولید و بهای تمام‌شده در یک محیط یکپارچه و حرفه‌ای.</p>
                    <div class="brand-points">
                        <div class="brand-point"><span>✓</span><span>مدیریت چند شرکت و کارگاه</span></div>
                        <div class="brand-point"><span>✓</span><span>کنترل موجودی و تولید</span></div>
                        <div class="brand-point"><span>✓</span><span>گزارش‌های دقیق بهای تمام‌شده</span></div>
                    </div>
                </div>
                <div class="brand-footer">Accounting · Industrial Cost Management</div>
            </div>

            <form class="login-form" @submit.prevent>
                <span class="form-kicker">ورود به حسابداری</span>
                <h2 class="form-title">خوش آمدید</h2>
                <p class="form-subtitle">برای ورود به پنل مدیریت، اطلاعات حساب خود را وارد کنید.</p>

                <div class="field">
                    <label for="email">ایمیل</label>
                    <input id="email" v-model="email" type="email" autocomplete="email" placeholder="name@example.com">
                </div>

                <div class="field">
                    <label for="password">رمز عبور</label>
                    <input id="password" v-model="password" type="password" autocomplete="current-password" placeholder="رمز عبور خود را وارد کنید">
                </div>

                <button class="login-button" type="submit">ورود به حساب</button>
                <div class="form-note">دسترسی شما بر اساس نقش و مجوزهای سازمانی کنترل می‌شود.</div>
            </form>
        </section>
    </main>
</template>
