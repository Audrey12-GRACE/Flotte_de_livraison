<template>
  <main class="page">
    <div class="container">
      <AppHeader />
      <section class="intro">
        <div>
          <p class="eyebrow">OPÉRATIONS</p>
          <h1>Gestion des courses</h1>
          <p>Créez, affectez et suivez chaque livraison.</p>
        </div>
        <button class="button button-primary" type="button" @click="ouvrirCreation">Nouvelle course</button>
      </section>

      <section class="panel">
        <div class="panel-heading">
          <div>
            <h2>Liste des courses</h2>
            <p>{{ coursesFiltrees.length }} résultat{{ coursesFiltrees.length > 1 ? 's' : '' }}</p>
          </div>
          <div class="filters">
            <label class="search-field">
              <input v-model="recherche" type="search" placeholder="Rechercher une adresse ou référence">
            </label>
            <select v-model="filtreStatut" aria-label="Filtrer par statut">
              <option value="">Tous les statuts</option>
              <option value="en_attente">En attente</option>
              <option value="en_cours">Prise en charge</option>
              <option value="livree">Livrée</option>
              <option value="annulee">Annulée</option>
            </select>
          </div>
        </div>

        <div v-if="coursesFiltrees.length" class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Référence</th>
                <th>Adresse</th>
                <th>Livreur</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="course in coursesFiltrees" :key="course.id">
                <td class="reference">#{{ course.id }}</td>
                <td class="adresse">{{ course.adresse }}</td>
                <td>{{ course.livreur?.nom || 'Non affecté' }}</td>
                <td class="amount">{{ course.montant.toLocaleString('fr-FR') }} FCFA</td>
                <td>
                  <span class="badge" :class="classeStatut(course.statut)">{{ libelleStatut(course.statut) }}</span>
                </td>
                <td>
                  <div class="actions">
                    <button v-if="course.statut === 'en_attente'" class="small-button blue" type="button"
                      :disabled="courseEnTraitement === course.id || !course.livreur_id"
                      :title="course.livreur_id ? 'Prendre en charge cette course' : 'Affecter un livreur avant de prendre en charge cette course'"
                      @click="changerStatut(course, 'en_cours')">Prendre en charge</button>
                    <button v-if="course.statut === 'en_cours'" class="small-button green" type="button"
                      :disabled="courseEnTraitement === course.id"
                      @click="changerStatut(course, 'livree')">Marquer livrée</button>
                    <button v-if="course.statut === 'en_attente'" class="small-button grey" type="button"
                      @click="ouvrirModification(course)">Modifier</button>
                    <button v-if="course.statut === 'en_attente' || course.statut === 'en_cours'"
                      class="small-button danger" type="button" :disabled="courseEnTraitement === course.id"
                      @click="annuler(course)">Annuler</button>
                    <span v-if="course.statut === 'livree'" class="muted">Terminée</span>
                    <span v-if="course.statut === 'annulee'" class="muted">Conservée</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="empty-state">
          <h3>{{ courses.length ? 'Aucun résultat' : 'Aucune course enregistrée' }}</h3>
          <p v-if="courses.length">Essayez de modifier votre recherche ou votre filtre.</p>
          <p v-else>Les courses que vous créerez apparaîtront ici.</p>
          <button v-if="!courses.length" class="button button-primary" type="button" @click="ouvrirCreation">
            Créer une course
          </button>
        </div>
      </section>

      <div v-if="dialogueOuvert" class="modal-backdrop" @click.self="fermerDialogue">
        <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
          <button class="close-button" type="button" @click="fermerDialogue">Fermer</button>
          <p class="eyebrow">{{ courseModifiee ? 'MODIFICATION' : 'NOUVELLE LIVRAISON' }}</p>
          <h2 id="dialog-title">{{ courseModifiee ? 'Modifier la course' : 'Créer une course' }}</h2>
          <p class="dialog-description">Renseignez l’adresse, le montant et l’affectation.</p>
          <form class="course-form" @submit.prevent="enregistrerCourse">
            <label for="adresse">Adresse de livraison</label>
            <input id="adresse" v-model="form.adresse" required placeholder="Ex. Bè, Lomé">
            <div class="form-row">
              <div>
                <label for="montant">Montant (FCFA)</label>
                <input id="montant" v-model.number="form.montant" type="number" min="1" required placeholder="0">
              </div>
              <div>
                <label for="livreur">Livreur </label>
                <select id="livreur" v-model="form.livreurId">
                  <option value="">Non affectée</option>
                  <option v-for="livreur in livreurs" :key="livreur.id" :value="livreur.id">{{ livreur.nom }}</option>
                </select>
              </div>
            </div>
            <p v-if="!livreurs.length" class="form-hint">Vous pourrez affecter un livreur après en avoir ajouté.</p>
            <p v-if="erreurFormulaire" class="form-error" role="alert">{{ erreurFormulaire }}</p>
            <div class="dialog-actions">
              <button class="button button-secondary" type="button" @click="fermerDialogue">Annuler</button>
              <button class="button button-primary" type="submit">
                {{ courseModifiee ? 'Enregistrer' : 'Créer la course' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </main>
</template>

<script setup>
const apiBase = useRuntimeConfig().public.apiBase

const courses = ref([])
const livreurs = ref([])

const recherche = ref('')
const filtreStatut = ref('')
const dialogueOuvert = ref(false)
const courseModifiee = ref(null)
const erreurFormulaire = ref('')
const courseEnTraitement = ref(null)
const form = ref({ adresse: '', montant: null, livreurId: '' })

async function chargerCourses() {
  try {
    courses.value = await $fetch(apiBase + '/courses')
  } catch (e) {
    console.log('erreur chargement des courses', e)
  }
}

async function chargerLivreurs() {
  try {
    livreurs.value = await $fetch(apiBase + '/livreurs')
  } catch (e) {
    console.log('erreur chargement des livreurs', e)
  }
}

async function charger() {
  await chargerLivreurs()
  await chargerCourses()
}

onMounted(charger)

const coursesFiltrees = computed(() => {
  const texte = recherche.value.trim().toLowerCase()

  return courses.value.filter(c => {
    if (filtreStatut.value && c.statut !== filtreStatut.value) return false
    if (!texte) return true

    const adresse = c.adresse.toLowerCase()
    return String(c.id) === texte.replace('#', '') || adresse.includes(texte)
  })
})

function nomLivreur(id) {
  const l = livreurs.value.find(l => l.id === id)
  if (l) return l.nom
  return 'Non affectée'
}

function libelleStatut(statut) {
  if (statut === 'en_attente') return 'En attente'
  if (statut === 'en_cours') return 'Prise en charge'
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

function ouvrirCreation() {
  // on recharge les livreurs pour avoir ceux ajoutés depuis la page livreurs
  chargerLivreurs()
  courseModifiee.value = null
  form.value = { adresse: '', montant: null, livreurId: '' }
  erreurFormulaire.value = ''
  dialogueOuvert.value = true
}

function ouvrirModification(course) {
  chargerLivreurs()
  courseModifiee.value = course
  form.value = {
    adresse: course.adresse,
    montant: course.montant,
    livreurId: course.livreur_id || ''
  }
  erreurFormulaire.value = ''
  dialogueOuvert.value = true
}

function fermerDialogue() {
  dialogueOuvert.value = false
}

async function enregistrerCourse() {
  erreurFormulaire.value = ''
  await envoyerCourse(false)
}

async function envoyerCourse(confirmer) {
  const data = {
    adresse: form.value.adresse,
    montant: form.value.montant,
    livreur_id: form.value.livreurId || null,
    confirmer
  }

  try {
    if (courseModifiee.value) {
      await $fetch(apiBase + '/courses/' + courseModifiee.value.id, { method: 'PATCH', body: data })
    } else {
      await $fetch(apiBase + '/courses', { method: 'POST', body: data })
    }
    await chargerCourses()
    fermerDialogue()
  } catch (e) {
    if (e?.status === 409 || e?.statusCode === 409) {
      if (confirm(e.data?.message || 'Ce livreur a déjà une course en cours. L’affecter quand même ?')) {
        await envoyerCourse(true)
      }
      return
    }
    erreurFormulaire.value = e?.data?.message || 'Impossible d’enregistrer la course.'
  }
}

async function changerStatut(course, statut) {
  courseEnTraitement.value = course.id

  try {
    if (statut === 'annulee') {
      await $fetch(apiBase + '/courses/' + course.id + '/annuler', { method: 'PATCH' })
    } else {
      await $fetch(apiBase + '/courses/' + course.id + '/avancer', { method: 'PATCH' })
    }
    // on recharge la liste pour afficher le nouveau statut et les bons boutons
    await chargerCourses()
  } catch (e) {
    alert(e?.data?.message || 'Impossible de modifier cette course.')
  } finally {
    courseEnTraitement.value = null
  }
}

function annuler(course) {
  if (confirm('Annuler cette course ?')) {
    changerStatut(course, 'annulee')
  }
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
  padding: 0 24px 56px;
}

.intro {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
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
  padding: 0 14px;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
}

.button-primary {
  background: #4563df;
  color: #fff;
}

.button-primary:hover {
  background: #354fca;
}

.button-secondary {
  border: 1px solid #e1e6ef;
  background: #fff;
  color: #58647a;
}

.panel {
  overflow: hidden;
  border: 1px solid #e9edf4;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 8px 30px rgb(32 48 85 / 4%);
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 22px;
}

.panel-heading h2 {
  margin: 0;
  color: #202b40;
  font-size: 17px;
}

.panel-heading p {
  margin: 5px 0 0;
  color: #8791a3;
  font-size: 12px;
}

.filters {
  display: flex;
  gap: 10px;
}

.search-field {
  display: flex;
  width: min(300px, 35vw);
  height: 38px;
  align-items: center;
  padding: 0 10px;
  border: 1px solid #e1e6ef;
  border-radius: 8px;
  color: #8791a3;
}

.search-field input {
  width: 100%;
  border: 0;
  outline: 0;
  color: #303b50;
  font: inherit;
  font-size: 11px;
}

.filters select,
.course-form input,
.course-form select {
  min-width: 0;
  height: 40px;
  padding: 0 10px;
  border: 1px solid #e1e6ef;
  border-radius: 8px;
  outline: none;
  background: #fff;
  color: #455168;
  font: inherit;
  font-size: 12px;
}

.filters select:focus,
.course-form input:focus,
.course-form select:focus {
  border-color: #7086ed;
  box-shadow: 0 0 0 3px rgb(69 99 223 / 12%);
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
  padding: 12px 17px;
  color: #7c879a;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .07em;
  text-transform: uppercase;
}

td {
  padding: 14px 17px;
  border-top: 1px solid #edf0f5;
  color: #455168;
  font-size: 12px;
}

.reference {
  color: #7c879a;
  font-size: 11px;
  font-weight: 650;
}

.adresse {
  color: #303b50;
  font-weight: 650;
}

.amount {
  color: #27334a;
  font-weight: 700;
}

.badge {
  display: inline-flex;
  align-items: center;
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

.actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.small-button {
  padding: 7px 8px;
  border: 0;
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
  font-size: 10px;
  font-weight: 700;
}

.small-button:disabled {
  cursor: wait;
  opacity: .5;
}

.blue {
  background: #edf1ff;
  color: #4563df;
}

.green {
  background: #e8f7ef;
  color: #218653;
}

.grey {
  background: #f1f3f8;
  color: #66748b;
}

.danger {
  background: #fff0ed;
  color: #bd5947;
}

.muted {
  color: #8993a3;
  font-size: 10px;
}

.empty-state {
  display: flex;
  min-height: 280px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  text-align: center;
}

.empty-state h3 {
  margin: 0 0 5px;
  color: #303b50;
  font-size: 15px;
}

.empty-state p {
  margin: 0 0 16px;
  color: #8791a3;
  font-size: 12px;
}

.modal-backdrop {
  position: fixed;
  z-index: 10;
  inset: 0;
  display: grid;
  place-items: center;
  overflow-y: auto;
  padding: 20px;
  background: rgb(23 32 51 / 45%);
}

.dialog {
  position: relative;
  width: min(500px, 100%);
  padding: 26px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 20px 70px rgb(15 25 50 / 20%);
}

.close-button {
  position: absolute;
  top: 16px;
  right: 17px;
  padding: 7px 10px;
  border: 0;
  border-radius: 8px;
  background: #f1f3f8;
  color: #66748b;
  cursor: pointer;
  font: inherit;
  font-size: 11px;
  font-weight: 650;
}

.dialog h2 {
  margin: 0;
  color: #202b40;
  font-size: 21px;
}

.dialog-description {
  margin: 7px 0 20px;
  color: #8791a3;
  font-size: 12px;
}

.course-form {
  display: grid;
  gap: 8px;
}

.course-form label {
  margin-top: 4px;
  color: #48546a;
  font-size: 11px;
  font-weight: 650;
}

.course-form input,
.course-form select {
  width: 100%;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-row>div {
  display: grid;
  gap: 8px;
}

.form-hint {
  margin: 2px 0 0;
  color: #8791a3;
  font-size: 11px;
}

.form-error {
  margin: 2px 0 0;
  color: #bd5947;
  font-size: 11px;
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 14px;
}

@media (max-width: 760px) {
  .container {
    padding-right: 16px;
    padding-left: 16px;
  }

  .panel-heading {
    align-items: flex-start;
    flex-direction: column;
    padding: 18px;
  }

  .filters {
    width: 100%;
  }

  .search-field {
    width: auto;
    flex: 1;
  }

  .filters select {
    max-width: 42%;
  }
}

@media (max-width: 480px) {
  .intro {
    align-items: flex-start;
    flex-direction: column;
    padding-top: 28px;
  }

  .intro .button {
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .dialog {
    padding: 22px 18px;
  }
}
</style>