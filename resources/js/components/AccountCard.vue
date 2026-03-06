<template>
    <div class="account-card" v-if="visible">
      <div class="account-card-header">
        <div class="account-email" v-if="email && email !== fullname">{{ email }}</div>
        <div class="account-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" fill="#e5e7eb" viewBox="0 0 24 24" width="48" height="48">
            <circle cx="12" cy="8" r="4" fill="#c7d2fe" />
            <path d="M4 20c0-2.21 3.58-4 8-4s8 1.79 8 4v1H4v-1z" fill="#c7d2fe" />
          </svg>
        </div>
        <div class="account-details">
          <div class="account-name">{{ fullname }}</div>
        </div>
      </div>
      <div class="account-divider"></div>
      <div class="account-actions">
        <button class="account-action-btn" @click="goToProfile">Profile</button>
        <button class="account-action-btn" @click="goToChangePassword">Change Password</button>
        <button class="account-action-btn logout" @click="logout">Logout</button>
      </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '../store/user';
import { CONSTANTS } from '../constants';

export default defineComponent({
  name: 'AccountCard',
  props: {
    fullname: { type: String, required: true },
    email: { type: String, default: '' },
    visible: { type: Boolean, default: false }
  },
  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    const goToProfile = () => {
      router.push('/profile');
    };
    const goToChangePassword = () => {
      router.push('/change-password');
    };
    const logout = () => {
      userStore.clearUser();
      document.cookie = `${CONSTANTS.USER_TOKEN}=; Max-Age=0; path=/`;
      router.push(CONSTANTS.ROUTE.LOGIN);
    };
    return { goToProfile, goToChangePassword, logout };
  }
});
</script>

<style scoped>

.account-card {
  position: absolute;
  top: 5.5rem;
  right: 1.5rem;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 1rem;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.13);
  padding: 1.5rem 2rem 1rem 2rem;
  min-width: 270px;
  z-index: 100;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: fadeInDown 0.2s;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.account-card-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 0.5rem;
}
.account-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
  box-shadow: 0 2px 8px rgba(59,130,246,0.08);
}
.account-details {
  text-align: center;
}
.account-name {
  font-weight: 600;
  color: #374151;
  font-size: 1.05rem;
  margin-bottom: 0.1rem;
}
.account-email {
  font-weight: 400;
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}
.account-divider {
  width: 100%;
  height: 1px;
  background: #e5e7eb;
  margin: 0.5rem 0 1rem 0;
}
.account-actions {
  display: flex;
  flex-direction: column;
  width: 100%;
  gap: 0.5rem;
}
.account-action-btn {
  background: none;
  border: none;
  color: #2563eb;
  font-weight: 500;
  font-size: 1rem;
  padding: 0.5rem 0;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.15s;
  text-align: left;
}
.account-action-btn:hover {
  background: #f3f4f6;
}
.logout {
  color: #ef4444;
}

@media (max-width: 600px) {
  .account-card {
    right: 0.5rem;
    left: 0.5rem;
    top: 3.5rem;
    min-width: unset;
    width: calc(100vw - 1rem);
    padding: 1rem 0.5rem;
    border-radius: 0.5rem;
  }
  .account-actions {
    gap: 0.25rem;
  }
  .btn {
    width: 100%;
    font-size: 1rem;
    padding: 0.75rem 1rem;
  }
  .account-info {
    font-size: 1rem;
    margin-bottom: 0.5rem;
  }
}

.account-info {
    margin-bottom: 1rem;
    font-weight: 600;
    color: #2563eb;
}

.account-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn {
    background: #f3f4f6;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: background 0.2s;
}

.btn:hover {
  background: #e0e7ef;
}
</style>
