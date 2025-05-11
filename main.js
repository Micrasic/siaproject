// Получаем все контейнеры с классом stages-container
const stagesContainers = document.querySelectorAll('.stages-container');

// Функция проверяет, является ли элемент видимым на экране
function isElementInView(element) {
  // Получаем размеры и координаты элемента
  const rect = element.getBoundingClientRect();
  // Проверяем, является ли элемент видимым
  return (
    rect.top >= 0 && // верхняя граница элемента выше верхней границы экрана
    rect.left >= 0 && // левая граница элемента левее левой границы экрана
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && // нижняя граница элемента ниже нижней границы экрана
    rect.right <= (window.innerWidth || document.documentElement.clientWidth) // правая граница элемента правее правой границы экрана
  );
}

// Добавляем обработчик события scroll на window
window.addEventListener('scroll', () => {
  // Перебираем все контейнеры
  stagesContainers.forEach((container) => {
    // Если контейнер видим, то делаем его видимым, иначе - скрытым
    if (isElementInView(container)) {
      container.classList.remove('hidden');
      container.classList.add('visible');
    } else {
      container.classList.remove('visible');
      container.classList.add('hidden');
    }
  });
});

// Получаем все вопросы
const questions = document.querySelectorAll('.question');

// Перебираем все вопросы
questions.forEach(question => {
  // Добавляем обработчик события click на каждый вопрос
  question.addEventListener('click', () => {
    // Переключаем класс active на вопрос
    question.classList.toggle('active');
    // Получаем span внутри вопроса
    const span = question.querySelector('span');
    // Меняем текст span в зависимости от состояния вопроса
    span.textContent = question.classList.contains('active') ? '-' : '+';
  });
});


// Показать кнопку при прокрутке
window.onscroll = function() {
  const button = document.getElementById("scrollToTop");
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    button.style.display = "block";
  } else {
    button.style.display = "none";
  }
};

// Прокрутка к верху страницы при нажатии на кнопку
document.getElementById("scrollToTop").onclick = function() {
  document.body.scrollTop = 0; // Для Safari
  document.documentElement.scrollTop = 0; // Для Chrome, Firefox, IE и Opera
};

document.querySelectorAll('.project-card').forEach(card => {
    card.addEventListener('click', function() {
        const images = JSON.parse(this.getAttribute('data-images'));
        if (images.length > 0) {
            modalImage.src = images[0]; // Показываем первое изображение
            modal.setAttribute('aria-hidden', 'false'); // Открываем модальное окно
        }
    });
});
document.querySelectorAll('.project-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // Не открывать модальное окно, если кликнули на кнопку
        if (e.target.tagName === 'BUTTON') return;

        const imagesAttr = this.getAttribute('data-images');
        if (!imagesAttr) return;

        let currentImages = [];
        try {
            currentImages = JSON.parse(imagesAttr);
        } catch {
            currentImages = [];
        }

        if (currentImages.length > 0) {
            let currentIndex = 0;
            const modalImage = document.getElementById('modalImage');
            modalImage.src = currentImages[currentIndex];

            const modal = document.getElementById('imageModal');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');

            document.getElementById('prevImageBtn').onclick = function() {
                currentIndex = (currentIndex > 0) ? currentIndex - 1 : currentImages.length - 1;
                modalImage.src = currentImages[currentIndex];
            };

            document.getElementById('nextImageBtn').onclick = function() {
                currentIndex = (currentIndex < currentImages.length - 1) ? currentIndex + 1 : 0;
                modalImage.src = currentImages[currentIndex];
            };

            document.getElementById('closeModal').onclick = function() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            };
        }
    });
});

function redirectTo(document) {
  window.location.href = 'info.php?doc=' + document;
}


function deleteConsultation(id) {
    if (!confirm('Вы уверены, что хотите удалить эту консультацию?')) return;
    fetch('app/delete_consultation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'consultation_id=' + encodeURIComponent(id)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('consultation-row-' + id);
            if (row) row.remove();
        } else {
            alert('Ошибка при удалении: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка сети при удалении консультации.'));
}

function updateConsultationStatus(id, btn) {
    fetch('app/update_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'consultation_id=' + encodeURIComponent(id)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('consultation-row-' + id);
            if (row) {
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) statusCell.textContent = 'Завершено';
                btn.disabled = true;
            }
        } else {
            alert('Ошибка при обновлении статуса: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка сети при обновлении статуса.'));
}

function deleteProject(projectId) {
    if (!confirm('Вы уверены, что хотите удалить этот проект?')) return;

    fetch('app/delete_project.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'project_id=' + encodeURIComponent(projectId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const projectCard = document.querySelector(`.project-card[data-id="${projectId}"]`);
            if (projectCard) {
                projectCard.remove();
            }
        } else {
            alert('Ошибка при удалении: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка сети при удалении проекта.'));
}

function openEditForm(projectId, name, description) {
    document.getElementById('editProjectId').value = projectId;
    document.getElementById('editProjectName').value = name;
    document.getElementById('editProjectDescription').value = description;
    document.getElementById('editProjectModal').style.display = 'block';
}

function closeEditForm() {
    document.getElementById('editProjectModal').style.display = 'none';
}

document.getElementById('editProjectForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);

    fetch('app/update_project.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const projectId = formData.get('project_id');
            const projectCard = document.querySelector(`.project-card[data-id="${projectId}"]`);
            if (projectCard) {
                projectCard.querySelector('.project-title').textContent = formData.get('name');
                projectCard.querySelector('.project-desc').textContent = formData.get('description');
                if (data.main_img) {
                    projectCard.querySelector('.project-image').src = data.main_img;
                }
            }
            closeEditForm();
        } else {
            alert('Ошибка при обновлении: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка сети при обновлении проекта.'));
});