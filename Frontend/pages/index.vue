<template>
  <main class="dashboard">
    <div class="container">
      <AppHeader />
      <section class="welcome">
        <div>
          <p class="eyebrow">VOTRE ESPACE DE PILOTAGE</p>
          <h1>Tableau de bord</h1>
          <p class="welcome-copy">Suivez votre flotte et l’activité de vos livraisons.</p>
        </div>
        <NuxtLink class="primary-link" to="/courses">Créer une course</NuxtLink>
      </section>

      <section class="stats-grid" aria-label="Indicateurs d'activité">
        <article class="stat-card">
          <p>Total des courses</p>
          <strong>{{ courses.length }}</strong>
          <span class="stat-note">toutes périodes</span>
        </article>
        <article class="stat-card">
          <p>En attente</p>
          <strong>{{ enAttente }}</strong>
          <span class="stat-note">à prendre en charge</span>
        </article>
        <article class="stat-card">
          <p>Prises en charge</p>
          <strong>{{ prisesEnCharge }}</strong>
          <span class="stat-note">en cours de livraison</span>
        </article>
        <article class="stat-card">
          <p>Chiffre d’affaires livré</p>
          <strong class="revenue">{{ chiffreAffaires.toLocaleString('fr-FR') }} <small>FCFA</small></strong>
          <span class="stat-note">{{ livrees }} course{{ livrees > 1 ? 's' : '' }} livrée{{ livrees > 1 ? 's' : '' }}</span>
        </article>
      </section>

      <section class="dashboard-grid">
        <article class="activity-card">
          <div class="card-heading">
            <div>
              <p class="eyebrow">SUIVI DES OPÉRATIONS</p>
              <h2>Courses récentes</h2>
            </div>
            <NuxtLink class="text-link" to="/courses">Toutes les courses</NuxtLink>
          </div>

          <div v-if="courses.length" class="activity-list">
            <div v-for="course in courses.slice(0, 5)" :key="course.id" class="activity-row">
              <div class="activity-details">
                <strong>{{ course.adresse }}</strong>
                <span>#{{ course.id }} · {{ nomLivreur(course.livreur_id) }} · {{ course.montant.toLocaleString('fr-FR') }} FCFA</span>
              </div>
              <span class="badge" :class="classeStatut(course.statut)">{{ libelleStatut(course.statut) }}</span>
            </div>
          </div>

          <div v-else class="empty-state">
            <strong>Aucune course pour le moment</strong>
            <p>Les courses que vous créerez apparaîtront dans ce tableau de bord.</p>
            <NuxtLink class="text-link" to="/courses">Créer une course</NuxtLink>
          </div>
        </article>

        <aside class="side-column">
          <article class="quick-card">
            <p class="eyebrow">VOTRE ÉQUIPE</p>
            <h2>{{ livreurs.length }} {{ livreurs.length > 1 ? 'livreurs enregistrés' : 'livreur enregistré' }}</h2>
            <p>Gérez les coordonnées, véhicules et zones de vos livreurs.</p>
            <NuxtLink class="quick-link" to="/livreurs">Gérer les livreurs</NuxtLink>
          </article>
          <article class="quick-card report-card">
            <p class="eyebrow">CHIFFRES & RAPPORTS</p>
            <h2>Votre activité, sur la période de votre choix.</h2>
            <p>Filtrez les courses livrées et annulées et exportez le rapport.</p>
            <NuxtLink class="quick-link" to="/suivi">Consulter le suivi</NuxtLink>
          </article>
        </aside>
      </section>

      <footer class="footer">
        <span>Grace Livraison</span>
        <span>Données enregistrées dans la base MySQL.</span>
      </footer>
    </div>
  </main>
</template>

<script setup>
const apiBase = useRuntimeConfig().public.apiBase

const courses = ref([])
const livreurs = ref([])

onMounted(async () => {
  try {
    courses.value = await $fetch(apiBase + '/courses')
    livreurs.value = await $fetch(apiBase + '/livreurs')
  } catch (e) {
    console.log('erreur chargement dashboard', e)
  }
})

const enAttente = computed(() => courses.value.filter(c => c.statut === 'en_attente').length)
const prisesEnCharge = computed(() => courses.value.filter(c => c.statut === 'en_cours').length)
const livrees = computed(() => courses.value.filter(c => c.statut === 'livree').length)

const chiffreAffaires = computed(() => {
  let total = 0
  for (const c of courses.value) {
    if (c.statut === 'livree') {
      total += c.montant
    }
  }
  return total
})

function nomLivreur(id) {
  const l = livreurs.value.find(l => l.id === id)
  if (l) return l.nom
  return 'Non assigné'
}

function libelleStatut(statut) {
  if (statut === 'en_attente') return 'En attente'
  if (statut === 'en_cours') return 'En cours'
  if (statut === 'livree') return 'Livrée'
  if (statut === 'annulee') return 'Annulée'
  return statut
}

function classeStatut(statut) {
  if (statut === 'en_attente') return 'pending'
  if (statut === 'en_cours') return 'progress'
  if (statut === 'livree') return 'delivered'
  return 'cancelled'
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.container {
  width: min(1160px, 100%);
  margin: 0 auto;
  padding: 0 24px 30px;
}

.welcome {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  padding: 40px 0 26px;
}

.eyebrow {
  margin: 0 0 7px;
  color: #7c879a;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .12em;
}

.welcome h1 {
  margin: 0;
  color: #172033;
  font-size: clamp(27px, 4vw, 35px);
  font-weight: 750;
  letter-spacing: -.04em;
}

.welcome-copy {
  margin: 8px 0 0;
  color: #7b8699;
  font-size: 14px;
}

.primary-link {
  display: inline-flex;
  min-height: 42px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 15px;
  border-radius: 9px;
  background: #4563df;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  transition: background 160ms ease;
}

.primary-link:hover {
  background: #354fca;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.stat-card,
.activity-card,
.quick-card {
  border: 1px solid #e9edf4;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 8px 30px rgb(32 48 85 / 4%);
}

.stat-card {
  min-width: 0;
  padding: 18px;
}

.stat-card>p {
  margin: 0 0 6px;
  color: #788397;
  font-size: 12px;
  font-weight: 600;
}

.stat-card>strong {
  display: block;
  overflow: hidden;
  color: #202b40;
  font-size: 26px;
  font-weight: 750;
  letter-spacing: -.04em;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stat-card>strong.revenue {
  font-size: 21px;
}

.revenue small {
  color: #778297;
  font-size: 10px;
  letter-spacing: 0;
}

.stat-note {
  display: block;
  margin-top: 4px;
  color: #9aa3b2;
  font-size: 10px;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(240px, .85fr);
  gap: 16px;
  margin-top: 20px;
}

.activity-card {
  min-height: 330px;
  overflow: hidden;
}

.card-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 21px 22px 17px;
}

.card-heading .eyebrow {
  margin-bottom: 6px;
}

.card-heading h2,
.quick-card h2 {
  margin: 0;
  color: #202b40;
  font-size: 17px;
  font-weight: 700;
}

.text-link,
.quick-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #4563df;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
}

.text-link:hover,
.quick-link:hover {
  color: #2e49c2;
}

.activity-row {
  display: flex;
  min-height: 68px;
  align-items: center;
  gap: 11px;
  padding: 11px 21px;
  border-top: 1px solid #edf0f5;
}

.activity-details {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 5px;
}

.activity-details strong {
  overflow: hidden;
  color: #303b50;
  font-size: 12px;
  text-overflow: ellipsis;
}

.activity-details>span {
  overflow: hidden;
  color: #8993a3;
  font-size: 10px;
  text-overflow: ellipsis;
}

.badge {
  flex: 0 0 auto;
  padding: 6px 8px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 650;
}

.pending {
  background: #f1f3f6;
  color: #697586;
}

.progress {
  background: #fff5df;
  color: #b7791f;
}

.delivered {
  background: #e8f7ef;
  color: #218653;
}

.cancelled {
  background: #fff0ed;
  color: #bd5947;
}

.empty-state {
  display: flex;
  min-height: 245px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 22px;
  border-top: 1px solid #edf0f5;
  text-align: center;
}

.empty-state strong {
  margin-top: 3px;
  color: #455168;
  font-size: 13px;
}

.empty-state p {
  margin: 0 0 4px;
  color: #8791a3;
  font-size: 11px;
}

.side-column {
  display: grid;
  gap: 14px;
}

.quick-card {
  display: flex;
  min-height: 158px;
  flex-direction: column;
  align-items: flex-start;
  padding: 18px;
}

.quick-card .eyebrow {
  margin-bottom: 6px;
}

.quick-card h2 {
  font-size: 14px;
  line-height: 1.45;
}

.quick-card>p:not(.eyebrow) {
  margin: 7px 0 12px;
  color: #818c9e;
  font-size: 11px;
  line-height: 1.5;
}

.quick-link {
  margin-top: auto;
}

.footer {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 22px 0 0;
  color: #99a2b0;
  font-size: 10px;
}

.footer span:first-child {
  flex: 0 0 auto;
  color: #6e7a8f;
  font-weight: 650;
}

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .side-column {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 560px) {
  .container {
    padding-right: 16px;
    padding-left: 16px;
  }

  .welcome {
    align-items: flex-start;
    flex-direction: column;
    padding: 30px 0 21px;
  }

  .primary-link {
    align-self: stretch;
  }

  .stats-grid {
    gap: 10px;
  }

  .stat-card {
    padding: 14px;
  }

  .stat-card>strong {
    font-size: 22px;
  }

  .stat-card>strong.revenue {
    font-size: 18px;
  }

  .dashboard-grid {
    margin-top: 14px;
  }

  .side-column {
    grid-template-columns: 1fr;
  }

  .card-heading {
    align-items: flex-start;
    flex-direction: column;
    padding: 18px 16px 14px;
  }

  .activity-row {
    gap: 8px;
    padding-right: 12px;
    padding-left: 12px;
  }

  .activity-details strong {
    font-size: 11px;
  }

  .badge {
    padding: 5px 6px;
    font-size: 9px;
  }

  .footer {
    flex-direction: column;
  }
}
</style>