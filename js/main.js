/* ============================================================
   main.js — PortfolioGen
   Sections:
     1. SHARED   — Utility functions used across all pages
     2. create.html — Multi-step form logic
        2a. State & step navigation
        2b. Validation
        2c. Character counters
        2d. Skills dynamic list
        2e. Projects dynamic list
        2f. Collect data & submit
   ============================================================ */


/* ============================================================
   1. SHARED — Utility Functions
   ============================================================ */

function isValidEmail(v) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

function isValidUrl(v) {
  try {
    var u = new URL(v);
    return u.protocol === 'http:' || u.protocol === 'https:';
  } catch (e) {
    return false;
  }
}


/* ============================================================
   2. create.html — Multi-step Form
   ============================================================ */

/* ----------------------------------------------------------
   2a. State & Step Navigation  (functions global so onclick works)
---------------------------------------------------------- */
var currentStep = 1;
var totalSteps  = 5;
var existingProfilePhoto = '';

function showStep(n) {
  document.querySelectorAll('.form-step').forEach(function (el) {
    el.classList.remove('active');
  });
  document.getElementById('step' + n).classList.add('active');

  document.querySelectorAll('.step').forEach(function (el) {
    var s = parseInt(el.getAttribute('data-step'));
    el.classList.remove('active', 'done');
    if (s === n) el.classList.add('active');
    if (s < n)   el.classList.add('done');
  });

  for (var i = 1; i <= 4; i++) {
    var line = document.getElementById('line' + i);
    if (i < n) line.classList.add('done');
    else        line.classList.remove('done');
  }
  currentStep = n;
}

function nextStep(from) {
  if (!validateStep(from)) return;
  if (from < totalSteps) showStep(from + 1);
}

function prevStep(from) {
  if (from > 1) showStep(from - 1);
}

/* ----------------------------------------------------------
   2b. Validation
---------------------------------------------------------- */
function showErr(id, msg) {
  var el = document.getElementById('err-' + id);
  if (!el) return;
  if (msg) el.textContent = msg;
  el.classList.add('visible');
  var input = document.getElementById(id);
  if (input) input.classList.add('error');
}

function clearErr(id) {
  var el = document.getElementById('err-' + id);
  if (el) el.classList.remove('visible');
  var input = document.getElementById(id);
  if (input) input.classList.remove('error');
}

function valueOf(id) {
  var el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

function clearMany(ids) {
  ids.forEach(clearErr);
}

function markRequired(fields) {
  var valid = true;
  fields.forEach(function (id) {
    clearErr(id);
    if (!valueOf(id)) {
      showErr(id);
      valid = false;
    }
  });
  return valid;
}

function validateOptionalUrls(fields) {
  var valid = true;
  fields.forEach(function (id) {
    clearErr(id);
    var v = valueOf(id);
    if (v && !isValidUrl(v)) {
      showErr(id, 'Please enter a valid URL.');
      valid = false;
    }
  });
  return valid;
}

function hasEmptyInput(selector) {
  var empty = false;
  document.querySelectorAll(selector).forEach(function (inp) {
    if (!inp.value.trim()) empty = true;
  });
  return empty;
}

function setGroupError(id, msg) {
  var err = document.getElementById(id);
  if (!err) return;
  err.textContent = msg || '';
  if (msg) err.classList.add('visible');
  else err.classList.remove('visible');
}

function validateStep(step) {
  var valid = true;

  if (step === 1) {
    valid = markRequired(['firstName', 'lastName', 'jobTitle']) && valid;
    valid = validateOptionalUrls(['website']) && valid;
  }

  if (step === 2) {
    clearMany(['bioShort', 'bioLong']);

    if (valueOf('bioShort').length < 10) {
      showErr('bioShort', 'Must be at least 10 characters.');
      valid = false;
    }

    if (valueOf('bioLong').length < 30) {
      showErr('bioLong', 'Must be at least 30 characters.');
      valid = false;
    }
  }

  if (step === 3) {
    setGroupError('err-skills', '');

    if (skills.length === 0) {
      setGroupError('err-skills', 'Please add at least one skill.');
      valid = false;
    } else if (hasEmptyInput('.skill-name')) {
      setGroupError('err-skills', 'Please fill in all skill names.');
      valid = false;
    }
  }

  if (step === 4) {
    setGroupError('err-projects', '');

    if (projects.length === 0) {
      setGroupError('err-projects', 'Please add at least one project.');
      valid = false;
    } else if (hasEmptyInput('.proj-title')) {
      setGroupError('err-projects', 'Please fill in all project titles.');
      valid = false;
    }
  }

  if (step === 5) {
    clearErr('email');
    var em = valueOf('email');

    if (!em || !isValidEmail(em)) {
      showErr('email', 'Please enter a valid email address.');
      valid = false;
    }

    valid = validateOptionalUrls(['github', 'linkedin', 'twitter', 'instagram']) && valid;
  }

  return valid;
}

/* Clear error live as user types */
document.querySelectorAll('input, textarea').forEach(function (el) {
  el.addEventListener('input', function () {
    if (el.id) clearErr(el.id);
  });
});

/* ----------------------------------------------------------
   2c. Character Counters
---------------------------------------------------------- */
function setupCounter(id, max) {
  var inp = document.getElementById(id);
  var ctr = document.getElementById('counter-' + id);
  if (!inp || !ctr) return;

  inp.setAttribute('maxlength', max);

  inp.addEventListener('input', function () {
    var len = inp.value.length;
    ctr.textContent = len + ' / ' + max;
    ctr.classList.toggle('warn', len > max * 0.9);
  });
}

setupCounter('bioShort', 120);
setupCounter('bioLong', 1000);

/* ----------------------------------------------------------
   2d. Skills Dynamic List
---------------------------------------------------------- */
var skillCount = 0;
var skills = [];

function addSkill(name, level) {
  skillCount++;
  var id = 'skill-' + skillCount;
  skills.push(id);

  var list = document.getElementById('skillsList');
  if (!list) return;

  var row = document.createElement('div');
  row.className = 'skill-row';
  row.id = id;

  row.innerHTML =
    '<input type="text" class="skill-name" placeholder="e.g. Python, React, MySQL..." value="' + (name || '') + '" />' +
    '<input type="number" min="0" max="100" placeholder="%" value="' + (level !== undefined ? level : 80) + '" />' +
    '<button class="remove-btn" onclick="removeSkill(\'' + id + '\')" title="Remove">&#x2715;</button>';

  list.appendChild(row);
}

function removeSkill(id) {
  var el = document.getElementById(id);
  if (el) el.remove();
  skills = skills.filter(function (s) { return s !== id; });
}

/* ----------------------------------------------------------
   2e. Projects Dynamic List
---------------------------------------------------------- */
var projectCount = 0;
var projects = [];

function addProject() {
  var projectData = arguments.length > 0 ? arguments[0] : null;
  projectCount++;
  var id = 'project-' + projectCount;
  projects.push(id);

  var list = document.getElementById('projectsList');
  if (!list) return;

  var card = document.createElement('div');
  card.className = 'project-card-form';
  card.id = id;

  card.innerHTML =
    '<div class="project-header">' +
      '<span>Project #' + projectCount + '</span>' +
      '<div style="display:flex;gap:6px;align-items:center;">' +
        '<button class="remove-btn" type="button" onclick="moveProjectUp(\'' + id + '\')" title="Move up">&#8593;</button>' +
        '<button class="remove-btn" type="button" onclick="moveProjectDown(\'' + id + '\')" title="Move down">&#8595;</button>' +
        '<button class="remove-btn" type="button" onclick="removeProject(\'' + id + '\')" title="Remove">&#x2715;</button>' +
      '</div>' +
    '</div>' +
    '<div class="field-group">' +
      '<label>Project Title <span class="req">*</span></label>' +
      '<input type="text" class="proj-title" placeholder="e.g. Portfolio Generator Web App" maxlength="100" />' +
    '</div>' +
    '<div class="field-group">' +
      '<label>Description</label>' +
      '<textarea class="proj-desc" rows="3" placeholder="Describe what this project does and the technologies used..."></textarea>' +
    '</div>' +
    '<div class="field-group">' +
      '<label>Project Image</label>' +
      '<input type="file" class="proj-image" accept="image/*" />' +
      '<div class="field-hint proj-image-hint"></div>' +
    '</div>' +
    '<div class="two-col">' +
      '<div class="field-group">' +
        '<label>Live Demo URL</label>' +
        '<input type="url" class="proj-demo" placeholder="https://..." />' +
      '</div>' +
      '<div class="field-group">' +
        '<label>GitHub / Source URL</label>' +
        '<input type="url" class="proj-repo" placeholder="https://github.com/..." />' +
      '</div>' +
    '</div>' +
    '<div class="field-group">' +
      '<label>Tags / Technologies</label>' +
      '<input type="text" class="proj-tags" placeholder="e.g. HTML, CSS, PHP, MySQL" maxlength="150" />' +
      '<div class="field-hint">Comma-separated tags shown as badges on the project card.</div>' +
    '</div>' +
    '<div class="field-group">' +
      '<label><input type="checkbox" class="proj-featured" /> Mark as featured project</label>' +
    '</div>';

  list.appendChild(card);

  if (projectData) {
    card.querySelector('.proj-title').value = projectData.title || '';
    card.querySelector('.proj-desc').value = projectData.description || '';
    card.querySelector('.proj-demo').value = projectData.demo || '';
    card.querySelector('.proj-repo').value = projectData.repo || '';
    card.querySelector('.proj-tags').value = projectData.tags || '';
    card.querySelector('.proj-featured').checked = !!projectData.featured;
    card.dataset.existingImage = projectData.image || '';

    var hint = card.querySelector('.proj-image-hint');
    if (hint && projectData.image) {
      hint.textContent = 'Current image kept unless you upload a new one.';
    }
  }
}

function removeProject(id) {
  var el = document.getElementById(id);
  if (el) el.remove();
  projects = projects.filter(function (p) { return p !== id; });
}

function moveProjectUp(id) {
  var el = document.getElementById(id);
  if (!el || !el.parentElement) return;
  var prev = el.previousElementSibling;
  if (prev) {
    el.parentElement.insertBefore(el, prev);
  }
}

function moveProjectDown(id) {
  var el = document.getElementById(id);
  if (!el || !el.parentElement) return;
  var next = el.nextElementSibling;
  if (next) {
    el.parentElement.insertBefore(next, el);
  }
}

/* ----------------------------------------------------------
   2f. Collect Data & Submit
---------------------------------------------------------- */
function collectData() {
  var skillsData = [];
  document.querySelectorAll('.skill-row').forEach(function (row) {
    var name = row.querySelector('.skill-name').value.trim();
    var level = parseInt(row.querySelector('input[type=number]').value) || 0;
    if (name) {
      skillsData.push({ name: name, level: Math.min(100, Math.max(0, level)) });
    }
  });

  var projectsData = [];
  document.querySelectorAll('.project-card-form').forEach(function (card, index) {
    var title = card.querySelector('.proj-title').value.trim();
    if (title) {
      projectsData.push({
        title:       title,
        description: card.querySelector('.proj-desc').value.trim(),
        demo:        card.querySelector('.proj-demo').value.trim(),
        repo:        card.querySelector('.proj-repo').value.trim(),
        tags:        card.querySelector('.proj-tags').value.trim(),
        image:       card.dataset.existingImage || '',
        featured:    card.querySelector('.proj-featured').checked,
        order:       index
      });
    }
  });

  return {
    portfolioTitle: document.getElementById('portfolioTitle').value.trim(),
    portfolioTheme: document.getElementById('portfolioTheme').value,
    isPublic:     !!document.getElementById('isPublic').checked,
    firstName:    document.getElementById('firstName').value.trim(),
    lastName:     document.getElementById('lastName').value.trim(),
    jobTitle:     document.getElementById('jobTitle').value.trim(),
    profilePhoto: existingProfilePhoto,
    location:     document.getElementById('location').value.trim(),
    website:      document.getElementById('website').value.trim(),
    bioShort:     document.getElementById('bioShort').value.trim(),
    bioLong:      document.getElementById('bioLong').value.trim(),
    yearsExp:     document.getElementById('yearsExp').value,
    skills:       skillsData,
    projects:     projectsData,
    email:        document.getElementById('email').value.trim(),
    phone:        document.getElementById('phone').value.trim(),
    github:       document.getElementById('github').value.trim(),
    linkedin:     document.getElementById('linkedin').value.trim(),
    twitter:      document.getElementById('twitter').value.trim(),
    instagram:    document.getElementById('instagram').value.trim()
  };
}

function submitForm() {
  if (!validateStep(5)) return;

  var data = collectData();
  var submitBtn = document.querySelector('.btn-submit');
  var formData = new FormData();
  var profileInput = document.getElementById('profilePhoto');

  formData.append('data', JSON.stringify(data));

  if (profileInput && profileInput.files && profileInput.files[0]) {
    formData.append('profile_photo', profileInput.files[0]);
  }

  document.querySelectorAll('.project-card-form').forEach(function (card, idx) {
    var projectInput = card.querySelector('.proj-image');
    if (projectInput && projectInput.files && projectInput.files[0]) {
      formData.append('project_images[' + idx + ']', projectInput.files[0]);
    }
  });

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
  }

  fetch('save_portfolio.php', {
    method: 'POST',
    body: formData
  })
    .then(function (res) {
      return res.json().catch(function () {
        return { ok: false, message: 'Invalid server response.' };
      });
    })
    .then(function (payload) {
      if (!payload.ok) {
        throw new Error(payload.message || 'Failed to save portfolio.');
      }

      var targetId = payload.portfolioId ? ('&id=' + encodeURIComponent(payload.portfolioId)) : '';
      window.location.href = 'preview.php?saved=1' + targetId;
    })
    .catch(function (err) {
      alert('Error: ' + err.message);
    })
    .finally(function () {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = '✅ Generate My Portfolio';
      }
    });
}

function setFieldValue(id, value) {
  var el = document.getElementById(id);
  if (el) {
    el.value = value || '';
  }
}

function replaceSkills(skillsData) {
  var list = document.getElementById('skillsList');
  if (!list) return;

  list.innerHTML = '';
  skills = [];
  skillCount = 0;

  if (!Array.isArray(skillsData) || skillsData.length === 0) {
    addSkill();
    return;
  }

  skillsData.forEach(function (item) {
    addSkill(item.name || '', item.level || 0);
  });
}

function replaceProjects(projectsData) {
  var list = document.getElementById('projectsList');
  if (!list) return;

  list.innerHTML = '';
  projects = [];
  projectCount = 0;

  if (!Array.isArray(projectsData) || projectsData.length === 0) {
    addProject();
    return;
  }

  projectsData.forEach(function (item) {
    addProject(item);
  });
}

function loadExistingPortfolio() {
  var params = new URLSearchParams(window.location.search || '');
  var selectedId = params.get('portfolio_id');
  var endpoint = 'get_portfolio.php';
  if (selectedId) {
    endpoint += '?id=' + encodeURIComponent(selectedId);
  }

  fetch(endpoint, { method: 'GET' })
    .then(function (res) {
      return res.json().catch(function () {
        return { ok: false, message: 'Invalid server response.' };
      });
    })
    .then(function (payload) {
      if (!payload.ok || !payload.hasPortfolio || !payload.data) {
        return;
      }

      var d = payload.data;

      setFieldValue('firstName', d.firstName);
      setFieldValue('lastName', d.lastName);
      setFieldValue('portfolioTitle', d.portfolioTitle || '');
      setFieldValue('portfolioTheme', d.portfolioTheme || 'aurora');
      var isPublicInput = document.getElementById('isPublic');
      if (isPublicInput) {
        isPublicInput.checked = !!d.isPublic;
      }
      setFieldValue('jobTitle', d.jobTitle);
      existingProfilePhoto = d.profilePhoto || '';
      setFieldValue('location', d.location);
      setFieldValue('website', d.website);
      setFieldValue('bioShort', d.bioShort);
      setFieldValue('bioLong', d.bioLong);
      setFieldValue('yearsExp', d.yearsExp);
      setFieldValue('email', d.email);
      setFieldValue('phone', d.phone);
      setFieldValue('github', d.github);
      setFieldValue('linkedin', d.linkedin);
      setFieldValue('twitter', d.twitter);
      setFieldValue('instagram', d.instagram);

      replaceSkills(d.skills);
      replaceProjects(d.projects);

      var profileHint = document.querySelector('#profilePhoto + .field-hint');
      if (profileHint && existingProfilePhoto) {
        profileHint.textContent = 'Current photo kept unless you upload a new one.';
      }
    })
    .catch(function () {
      // Keep page usable even if loading existing data fails.
    });
}

/* Run on create.html only */
if (document.getElementById('skillsList')) {
  addSkill('HTML / CSS', 85);
  addSkill('JavaScript', 75);
  addSkill('PHP', 60);
}

if (document.getElementById('projectsList')) {
  addProject();
}

if (document.getElementById('skillsList')) {
  loadExistingPortfolio();
}