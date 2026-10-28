<template>
  <main class="page">
    <div class="container">
      <AppHeader/>
      <section class="intro">
        <div>
          <p class="eyebrow">RAPPORTS</p>
          <h1>Suivi financier</h1>
          <p>Consultez les résultats des courses sur la période de votre choix.</p>
        </div>
        <button class="button export-button" type="button" :disabled="coursesPeriode.length === 0" @click="exporter">
          <span aria-hidden="true">↓</span> Exporter le tableau
        </button>
      </section>

      <section class="filter-card">
        <div class="filter-title">
          
          <div><strong>Personnaliser le rapport</strong><span>Choisissez une période et un livreur.</span></div>
        </div>
        <div class="filter-fields">
          <label>Du<input v-model="dateDebut" type="date"></label>
          <label>Au<input v-model="dateFin" type="date"></label>
          <label>Livreur
            <select v-model="livreurFiltre">
              <option value="">Tous les livreurs</option>
              <option v-for="livreur in livreurs" :key="livreur.id" :value="String(livreur.id)">
                {{ livreur.nom }}
              </option>
            </select>
          </label>
        </div>
        <p v-if="erreurDates" class="date-error" role="alert">{{ erreurDates }}</p>
      </section>

      <section class="metrics">
        <article class="metric revenue">
          
          <p>Chiffre d’affaires livré</p>
          <strong>{{ chiffreAffaires.toLocaleString('fr-FR') }} <small>FCFA</small></strong>
          <span class="metric-note">Basé uniquement sur les courses livrées</span>
        </article>
        <article class="metric">
          
          <p>Courses livrées</p>
          <strong>{{ coursesLivrees.length }}</strong>
          <span class="metric-note">sur la période sélectionnée</span>
        </article>
        <article class="metric">
          
          <p>Courses annulées</p>
          <strong>{{ coursesAnnulees.length }}</strong>
          <span class="metric-note">exclues du chiffre d’affaires</span>
        </article>
      </section>

      <section class="panel">
        <div class="panel-heading">
          <div>
            <p class="eyebrow">DÉTAIL DE LA PÉRIODE</p>
            <h2>Courses du rapport</h2>
          </div>
          <span class="count">{{ coursesPeriode.length }} course{{ coursesPeriode.length === 1 ? '' : 's' }}</span>
        </div>
        <div v-if="coursesPeriode.length" class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Référence</th>
                <th>Date</th>
                <th>Localisation</th>
                <th>Livreur</th>
                <th>Montant</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="course in coursesPeriode" :key="course.id">
                <td class="reference">#{{ course.id }}</td>
                <td>{{ dateAffichee(dateEvenement(course)) }}</td>
                <td><span class="route"><span aria-hidden="true"></span> {{ course.adresse
                    }}</span></td>
                <td>{{ nomLivreur(course.livreur_id) }}</td>
                <td class="amount">{{ course.montant.toLocaleString('fr-FR') }} FCFA</td>
                <td><span class="badge" :class="classeStatut(course.statut)">{{ libelleStatut(course.statut) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <span aria-hidden="true">◷</span>
          <strong>Aucune course sur cette période</strong>
          <p>Modifiez les dates ou ajoutez des courses pour afficher le rapport.</p>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
const apiBase = useRuntimeConfig().public.apiBase
const courses = ref([])
const livreurs = ref([])

async function chargerLivreurs() {
  try {
    livreurs.value = await $fetch(apiBase + '/livreurs')
  } catch (e) {
    console.log('erreur chargement des livreurs', e)
  }
}

async function chargerCourses() {
  try {
    courses.value = await $fetch(apiBase + '/courses')
  } catch (e) {
    console.log('erreur chargement des courses', e)
  }
}

onMounted(() => {
  chargerLivreurs()
  chargerCourses()
})
const aujourdHui = new Date()
const dateLocale = (date) => {
  const annee = date.getFullYear()
  const mois = String(date.getMonth() + 1).padStart(2, '0')
  const jour = String(date.getDate()).padStart(2, '0')
  return `${annee}-${mois}-${jour}`
}
const dateFin = ref(dateLocale(aujourdHui))
const debutDuMois = new Date(aujourdHui.getFullYear(), aujourdHui.getMonth(), 1)
const dateDebut = ref(dateLocale(debutDuMois))
const livreurFiltre = ref('')

const erreurDates = computed(() => {
  if (!dateDebut.value || !dateFin.value) return 'Veuillez sélectionner les deux dates.'
  if (dateDebut.value > dateFin.value) return 'La date de début doit précéder la date de fin.'
  return ''
})

// Date qui compte pour le rapport : livraison, sinon annulation, sinon création
const dateEvenement = (course) => course.livree_le || course.annulee_le || course.created_at
const dateCle = (valeur) => {
  if (!valeur) return ''
  return dateLocale(new Date(valeur))
}

const coursesPeriode = computed(() => {
  if (erreurDates.value) return []
  return courses.value.filter((course) => {
    const jour = dateCle(dateEvenement(course))
    const correspondLivreur = !livreurFiltre.value || String(course.livreur_id) === livreurFiltre.value
    return jour >= dateDebut.value && jour <= dateFin.value && correspondLivreur
  })
})
const coursesLivrees = computed(() => coursesPeriode.value.filter(course => course.statut === 'livree'))
const coursesAnnulees = computed(() => coursesPeriode.value.filter(course => course.statut === 'annulee'))
const chiffreAffaires = computed(() => coursesLivrees.value.reduce((total, course) => total + course.montant, 0))

const nomLivreur = (id) => livreurs.value.find(livreur => livreur.id === id)?.nom || 'Non affectée'
const dateAffichee = (date) => date
  ? new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
  : '—'
const libelleStatut = (statut) => ({
  en_attente: 'En attente',
  en_cours: 'Prise en charge',
  livree: 'Livrée',
  annulee: 'Annulée'
}[statut] || statut)
const classeStatut = (statut) => ({
  en_attente: 'pending',
  en_cours: 'progress',
  livree: 'delivered',
  annulee: 'cancelled'
}[statut] || 'pending')

const exporter = () => {
  const lignes = [
    ['Référence', 'Date', 'Arrivée', 'Livreur', 'Montant FCFA', 'Statut'],
    ...coursesPeriode.value.map(course => [
      course.id,
      dateCle(dateEvenement(course)),
      course.adresse,
      nomLivreur(course.livreur_id),
      course.montant,
      libelleStatut(course.statut)
    ])
  ]
  const contenu = '\uFEFF' + lignes.map(ligne =>
    ligne.map((valeur) => {
      const texte = String(valeur ?? '')
      const securise = /^[=+\-@\t\r]/.test(texte) ? `'${texte}` : texte
      return `"${securise.replaceAll('"', '""')}"`
    }).join(';')
  ).join('\r\n')
  const url = URL.createObjectURL(new Blob([contenu], { type: 'text/csv;charset=utf-8' }))
  const lien = document.createElement('a')
  lien.href = url
  lien.download = `rapport-courses-${dateDebut.value}-${dateFin.value}.csv`
  lien.click()
  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.container {
  width: min(1160px, 100%);
  margin: 0 auto;
  padding: 0 24px 48px;
}

.intro {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 38px 0 24px;
}

.eyebrow {
  margin: 0 0 7px;
  color: #7c879a;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .12em;
}

.intro h1 {
  margin: 0;
  color: #172033;
  font-size: clamp(26px, 4vw, 34px);
  letter-spacing: -.04em;
}

.intro p:last-child {
  margin: 8px 0 0;
  color: #7b8699;
  font-size: 14px;
}

.button {
  display: inline-flex;
  min-height: 40px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 13px;
  border: 1px solid #e1e6ef;
  border-radius: 8px;
  background: #fff;
  color: #455168;
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
}

.button:disabled {
  cursor: not-allowed;
  opacity: .5;
}

.filter-card,
.metric,
.panel {
  border: 1px solid #e9edf4;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 8px 30px rgb(32 48 85 / 4%);
}

.filter-card {
  padding: 20px;
}

.filter-title {
  display: flex;
  align-items: center;
  gap: 11px;
}

.filter-icon {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border-radius: 11px;
  background: #edf1ff;
  color: #4563df;
  font-size: 19px;
}

.filter-title div {
  display: grid;
  gap: 4px;
}

.filter-title strong {
  color: #303b50;
  font-size: 13px;
}

.filter-title div span {
  color: #8791a3;
  font-size: 11px;
}

.filter-fields {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-top: 18px;
}

.filter-fields label {
  display: grid;
  gap: 7px;
  color: #59657a;
  font-size: 11px;
  font-weight: 650;
}

.filter-fields input,
.filter-fields select {
  width: 100%;
  height: 39px;
  padding: 0 10px;
  border: 1px solid #e1e6ef;
  border-radius: 8px;
  outline: 0;
  background: #fff;
  color: #455168;
  font: inherit;
  font-size: 12px;
}

.filter-fields input:focus,
.filter-fields select:focus {
  border-color: #7086ed;
  box-shadow: 0 0 0 3px rgb(69 99 223 / 12%);
}

.date-error {
  margin: 12px 0 0;
  color: #bd5947;
  font-size: 11px;
}

.metrics {
  display: grid;
  grid-template-columns: 1.25fr 1fr 1fr;
  gap: 14px;
  margin: 18px 0;
}

.metric {
  min-width: 0;
  padding: 18px;
}

.metric-icon {
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border-radius: 10px;
  background: #edf1ff;
  color: #4563df;
  font-size: 17px;
  font-weight: 750;
}

.delivered-icon {
  background: #e8f7ef;
  color: #218653;
}

.cancelled-icon {
  background: #fff0ed;
  color: #bd5947;
}

.metric p {
  margin: 13px 0 5px;
  color: #788397;
  font-size: 12px;
  font-weight: 600;
}

.metric strong {
  display: block;
  overflow: hidden;
  color: #202b40;
  font-size: 25px;
  letter-spacing: -.03em;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.metric strong small {
  color: #778297;
  font-size: 11px;
  letter-spacing: 0;
}

.metric-note {
  display: block;
  margin-top: 6px;
  color: #9aa3b2;
  font-size: 10px;
}

.revenue {
  border-color: #dce4ff;
  background: linear-gradient(135deg, #fff, #f7f8ff);
}

.revenue .metric-icon {
  background: #4563df;
  color: #fff;
}

.panel {
  overflow: hidden;
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 22px;
}

.panel-heading h2 {
  margin: 0;
  color: #202b40;
  font-size: 17px;
}

.count {
  padding: 7px 10px;
  border-radius: 8px;
  background: #f1f3f8;
  color: #68748a;
  font-size: 11px;
  font-weight: 650;
}

.table-scroll {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  white-space: nowrap;
}

thead {
  background: #f8f9fc;
}

th {
  padding: 12px 16px;
  color: #7c879a;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .07em;
  text-transform: uppercase;
}

td {
  padding: 14px 16px;
  border-top: 1px solid #edf0f5;
  color: #59657a;
  font-size: 11px;
}

.reference {
  color: #7c879a;
  font-weight: 650;
}

.route {
  color: #303b50;
}

.route span {
  padding: 0 4px;
  color: #98a2b2;
}

.amount {
  color: #27334a;
  font-weight: 700;
}

.badge {
  display: inline-flex;
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
  display: grid;
  min-height: 210px;
  place-content: center;
  justify-items: center;
  gap: 8px;
  padding: 24px;
  color: #8791a3;
  text-align: center;
}

.empty-state>span {
  display: grid;
  width: 42px;
  height: 42px;
  place-items: center;
  border-radius: 13px;
  background: #f1f3ff;
  color: #526bd8;
  font-size: 22px;
}

.empty-state strong {
  color: #455168;
  font-size: 13px;
}

.empty-state p {
  margin: 0;
  font-size: 11px;
}

@media (max-width: 720px) {
  .container {
    padding-right: 16px;
    padding-left: 16px;
  }

  .metrics {
    grid-template-columns: 1fr 1fr;
  }

  .revenue {
    grid-column: 1 / -1;
  }

  .filter-fields {
    gap: 9px;
  }
}

@media (max-width: 480px) {
  .intro {
    align-items: flex-start;
    flex-direction: column;
    padding-top: 28px;
  }

  .filter-fields {
    grid-template-columns: 1fr;
  }

  .metrics {
    grid-template-columns: 1fr 1fr;
  }

  .metric {
    padding: 14px;
  }

  .metric strong {
    font-size: 21px;
  }

  .panel-heading {
    padding: 17px;
  }
}
</style>