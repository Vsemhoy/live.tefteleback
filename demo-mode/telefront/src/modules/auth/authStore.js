import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import api from '@/shared/utils/api';

const KNOWN_BROWSER_KEY = 'teftele-known-browser';

const readKnownBrowser = () => {
  try {
    return localStorage.getItem(KNOWN_BROWSER_KEY) === '1';
  } catch {
    return false;
  }
};

const writeKnownBrowser = (value) => {
  try {
    if (value) {
      localStorage.setItem(KNOWN_BROWSER_KEY, '1');
    } else {
      localStorage.removeItem(KNOWN_BROWSER_KEY);
    }
  } catch {
    // ignore
  }
};

/**
 * Стор авторизации.
 * JWT живёт в httpOnly cookie на сервере — здесь храним только публичные данные юзера.
 * persist сохраняет профиль в localStorage — чтобы UI не мигал при перезагрузке.
 *
 * known browser:
 * - ставим после успешного логина
 * - НЕ удаляем, если сессия просто умерла сама (forceLogout)
 * - удаляем только при явном logout
 */
export const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null,        // { id, name, email, avatar }
      isChecked: false,  // Прошли ли первичную проверку /auth/me при старте
      isKnownBrowser: readKnownBrowser(),

      setKnownBrowser: (value) => {
        writeKnownBrowser(value);
        set({ isKnownBrowser: value });
      },

      /**
       * Логин — сервер устанавливает httpOnly cookies (access + refresh)
       */
      login: async (email, password) => {
        const res = await api.post('/auth/login', { email, password });
        const { user } = res.data;
        get().setKnownBrowser(true);
        set({ user, isChecked: true });
        return user;
      },

      /**
       * Демо-вход — без пароля, короткий JWT, read-only
       */
      demoLogin: async () => {
        const res = await api.post('/auth/demo');
        const { user } = res.data;
        set({ user, isChecked: true });
        // Знакомым браузером не помечаем — демо не настоящий вход
        return user;
      },

      // Удобный геттер для read-only блокировки в UI
      isDemo: () => Boolean(get().user?.is_demo),

      /**
       * Проверка сессии при старте приложения — тихая, без редиректов.
       * known browser НЕ трогаем — он живёт независимо от сессии.
       */
      checkAuth: async () => {
        try {
          const res = await api.post('/auth/me');
          set({ user: res.data.user, isChecked: true });
        } catch {
          // 401 = просто не залогинен, это нормально
          // ВАЖНО: known browser тут НЕ трогаем
          set({ user: null, isChecked: true });
        }
      },

      /**
       * Выход — сервер инвалидирует refresh token и чистит cookies.
       * Удаляем метку "знакомый браузер" — следующий заход будет как новый.
       */
      logout: async () => {
        try {
          await api.post('/auth/logout');
        } catch {
          // Даже если сервер не ответил — локально всё равно чистим
        }
        get().setKnownBrowser(false);
        set({ user: null, isChecked: true });
      },

      /**
       * Принудительный локальный logout (когда refresh умер).
       * known browser НЕ трогаем — пользователь не нажимал Sign out,
       * это просто истёкшая сессия.
       */
      forceLogout: () => {
        set({ user: null, isChecked: true });
      },
    }),
    {
      name: 'teftele-auth',
      // Сохраняем только профиль юзера, не isChecked
      partialize: (state) => ({ user: state.user }),
    }
  )
);

// Если refresh токена умер — просто снимаем user,
// но НЕ удаляем метку "знакомый браузер"
window.addEventListener('auth:logout', () => {
  useAuthStore.getState().forceLogout();
});
