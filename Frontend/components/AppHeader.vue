<template>
  <header class="topbar">
    <NuxtLink class="brand" to="/" aria-label="Accueil Grace Livraison">
      <span class="brand-mark" aria-hidden="true">G</span>
      <span>Grace<span class="brand-light"> Livraison</span></span>
    </NuxtLink>
    <nav class="top-nav" aria-label="Navigation principale">
      <NuxtLink class="nav-link" to="/">Tableau de bord</NuxtLink>
      <NuxtLink class="nav-link" to="/courses">Courses</NuxtLink>
      <NuxtLink class="nav-link" to="/livreurs">Livreurs</NuxtLink>
      <NuxtLink class="nav-link" to="/suivi">Suivi financier</NuxtLink>
    </nav>
    
  </header>
</template>

<style scoped>
.topbar {
  display: flex;
  min-height: 76px;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  border-bottom: 1px solid #e8ecf3;
}

.brand {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 10px;
  color: #202b40;
  font-size: 16px;
  font-weight: 750;
  text-decoration: none;
}

.brand-mark {
  display: grid;
  width: 36px;
  height: 36px;
  place-items: center;
  border-radius: 11px;
  background: #4563df;
  color: #fff;
  font-size: 18px;
  font-weight: 750;
}

.brand-light {
  color: #778297;
  font-weight: 500;
}

.top-nav {
  display: flex;
  min-height: 76px;
  align-items: stretch;
  gap: clamp(12px, 2.4vw, 30px);
}

.nav-link {
  position: relative;
  display: inline-flex;
  align-items: center;
  color: #7a8598;
  font-size: 19px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  transition: color 160ms ease;
}

.nav-link:hover,
.nav-link.router-link-exact-active {
  color: #3f5dd7;
}

.nav-link.router-link-exact-active::after {
  position: absolute;
  right: 0;
  bottom: -1px;
  left: 0;
  height: 2px;
  background: #4563df;
  content: "";
}

.profile-avatar {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 auto;
  place-items: center;
  border: 1px solid #dfe5f4;
  border-radius: 50%;
  background: #e9edff;
  color: #4563df;
  font-size: 13px;
  font-weight: 750;
}

@media (max-width: 700px) {
  .topbar {
    min-height: 66px;
    flex-wrap: wrap;
    padding: 12px 0 0;
    gap: 8px 14px;
  }

  .brand {
    font-size: 14px;
  }

  .brand-mark {
    width: 32px;
    height: 32px;
  }

  .profile-avatar {
    width: 30px;
    height: 30px;
    margin-left: auto;
  }

  .top-nav {
    width: 100%;
    min-height: 42px;
    justify-content: space-between;
    gap: 8px;
  }

  .nav-link {
    font-size: 10px;
  }
}
</style>
