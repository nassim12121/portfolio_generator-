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

function validateStep(step) {
  var valid = true;

  if (step === 1) {
    ['firstName', 'lastName', 'jobTitle'].forEach(function (f) {
      clearErr(f);
      if (!document.getElementById(f).value.trim()) {
        showErr(f);
        valid = false;
      }
    });

    ['profilePhoto', 'website'].forEach(function (f) {
      clearErr(f);
      var v = document.getElementById(f).value.trim();
      if (v && !isValidUrl(v)) {
        showErr(f);
        valid = false;
      }
    });
  }

  if (step === 2) {
    clearErr('bioShort');
    clearErr('bioLong');

    if (document.getElementById('bioShort').value.trim().length < 10) {
      showErr('bioShort', 'Must be at least 10 characters.');
      valid = false;
    }

    if (document.getElementById('bioLong').value.trim().length < 30) {
      showErr('bioLong', 'Must be at least 30 characters.');
      valid = false;
    }
  }

  if (step === 3) {
    var errSkills = document.getElementById('err-skills');
    errSkills.classList.remove('visible');

    if (skills.length === 0) {
      errSkills.textContent = 'Please add at least one skill.';
      errSkills.classList.add('visible');
      valid = false;
    } else {
      var hasEmpty = false;
      document.querySelectorAll('.skill-name').forEach(function (inp) {
        if (!inp.value.trim()) hasEmpty = true;
      });

      if (hasEmpty) {
        errSkills.textContent = 'Please fill in all skill names.';
        errSkills.classList.add('visible');
        valid = false;
      }
    }
  }

  if (step === 4) {
    var errProjects = document.getElementById('err-projects');
    errProjects.classList.remove('visible');

    if (projects.length === 0) {
      errProjects.textContent = 'Please add at least one project.';
      errProjects.classList.add('visible');
      valid = false;
    } else {
      var hasEmptyProj = false;
      document.querySelectorAll('.proj-title').forEach(function (inp) {
        if (!inp.value.trim()) hasEmptyProj = true;
      });

      if (hasEmptyProj) {
        errProjects.textContent = 'Please fill in all project titles.';
        errProjects.classList.add('visible');
        valid = false;
      }
    }
  }

  if (step === 5) {
    clearErr('email');
    var em = document.getElementById('email').value.trim();

    if (!em || !isValidEmail(em)) {
      showErr('email', 'Please enter a valid email address.');
      valid = false;
    }

    ['github', 'linkedin', 'twitter', 'instagram'].forEach(function (f) {
      clearErr(f);
      var v = document.getElementById(f).value.trim();
      if (v && !isValidUrl(v)) {
        showErr(f, 'Please enter a valid URL.');
        valid = false;
      }
    });
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
      '<button class="remove-btn" onclick="removeProject(\'' + id + '\')" title="Remove">&#x2715;</button>' +
    '</div>' +
    '<div class="field-group">' +
      '<label>Project Title <span class="req">*</span></label>' +
      '<input type="text" class="proj-title" placeholder="e.g. Portfolio Generator Web App" maxlength="100" />' +
    '</div>' +
    '<div class="field-group">' +
      '<label>Description</label>' +
      '<textarea class="proj-desc" rows="3" placeholder="Describe what this project does and the technologies used..."></textarea>' +
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
    '</div>';

  list.appendChild(card);
}

function removeProject(id) {
  var el = document.getElementById(id);
  if (el) el.remove();
  projects = projects.filter(function (p) { return p !== id; });
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
  document.querySelectorAll('.project-card-form').forEach(function (card) {
    var title = card.querySelector('.proj-title').value.trim();
    if (title) {
      projectsData.push({
        title:       title,
        description: card.querySelector('.proj-desc').value.trim(),
        demo:        card.querySelector('.proj-demo').value.trim(),
        repo:        card.querySelector('.proj-repo').value.trim(),
        tags:        card.querySelector('.proj-tags').value.trim()
      });
    }
  });

  return {
    firstName:    document.getElementById('firstName').value.trim(),
    lastName:     document.getElementById('lastName').value.trim(),
    jobTitle:     document.getElementById('jobTitle').value.trim(),
    profilePhoto: document.getElementById('profilePhoto').value.trim(),
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
  console.log('Portfolio data:', data);
  alert('Form data collected! Check the browser console for the full data object.');
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