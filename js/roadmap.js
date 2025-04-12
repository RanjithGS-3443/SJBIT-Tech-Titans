// Roadmap functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle task status updates
    document.querySelectorAll('.task-status-btn').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const taskItem = this.closest('.task-item');
            const taskId = taskItem.getAttribute('data-task-id');
            
            // Don't proceed if task ID is 0 (not yet saved to database)
            if (taskId === '0') {
                alert('This task needs to be saved to the database first.');
                return;
            }

            const newStatus = this.getAttribute('data-status');
            
            try {
                // Disable all status buttons during update
                const allButtons = taskItem.querySelectorAll('.task-status-btn');
                allButtons.forEach(btn => btn.disabled = true);

                // Show loading state
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('task_id', taskId);
                formData.append('status', newStatus);

                const response = await fetch('roadmap.php', {
                    method: 'POST',
                    body: formData
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server response was not JSON');
                }

                const data = await response.json();
                
                if (data.success) {
                    // Update task status visually
                    taskItem.setAttribute('data-status', newStatus);
                    
                    // Update button states
                    allButtons.forEach(btn => {
                        const btnStatus = btn.getAttribute('data-status');
                        btn.classList.remove('btn-primary', 'btn-warning', 'btn-success');
                        btn.classList.remove('btn-outline-primary', 'btn-outline-warning', 'btn-outline-success');
                        
                        if (btnStatus === newStatus) {
                            btn.classList.add(`btn-${getStatusClass(btnStatus)}`);
                        } else {
                            btn.classList.add(`btn-outline-${getStatusClass(btnStatus)}`);
                        }
                        
                        // Update button icon
                        btn.innerHTML = getStatusIcon(btnStatus);
                    });

                    // Show success message
                    showToast('Task status updated successfully!', 'success');
                } else {
                    throw new Error(data.message || 'Failed to update task status');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast(error.message, 'danger');
            } finally {
                // Re-enable buttons and restore original content
                const allButtons = taskItem.querySelectorAll('.task-status-btn');
                allButtons.forEach(btn => {
                    btn.disabled = false;
                    const btnStatus = btn.getAttribute('data-status');
                    btn.innerHTML = getStatusIcon(btnStatus);
                });
            }
        });
    });

    // Handle resource links
    document.querySelectorAll('.resource-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const resourceUrl = this.href;
            if (resourceUrl) {
                window.open(resourceUrl, '_blank');
            }
            e.preventDefault();
        });
    });

    // Handle skill gap indicator clicks
    const skillGapIndicators = document.querySelectorAll('.skill-gap-indicator');
    skillGapIndicators.forEach(indicator => {
        indicator.addEventListener('click', function() {
            const skillName = this.dataset.skill;
            const gapLevel = this.dataset.gap;
            
            // Show skill details modal
            const modal = new bootstrap.Modal(document.getElementById('skillDetailsModal'));
            const modalTitle = document.querySelector('#skillDetailsModal .modal-title');
            const modalContent = document.querySelector('#skillDetailsModal .modal-body');
            
            modalTitle.textContent = `${skillName} Skill Details`;
            modalContent.innerHTML = `
                <div class="mb-3">
                    <h6>Current Gap Level: ${gapLevel}/3</h6>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: ${(gapLevel/3)*100}%"></div>
                    </div>
                </div>
                <div>
                    <h6>Recommended Actions:</h6>
                    <ul>
                        <li>Complete the suggested learning resources</li>
                        <li>Practice through hands-on exercises</li>
                        <li>Work on related projects</li>
                    </ul>
                </div>
            `;
            
            modal.show();
        });
    });

    // Function to update progress indicators
    function updateProgressIndicators() {
        const goals = document.querySelectorAll('.goal-card');
        goals.forEach(goal => {
            const tasks = goal.querySelectorAll('.task-item');
            const totalTasks = tasks.length;
            let completedTasks = 0;

            tasks.forEach(task => {
                if (task.getAttribute('data-status') === 'completed') {
                    completedTasks++;
                }
            });

            // Update progress bar if it exists
            const progressBar = goal.querySelector('.progress-bar');
            if (progressBar) {
                const progress = (completedTasks / totalTasks) * 100;
                progressBar.style.width = `${progress}%`;
                progressBar.setAttribute('aria-valuenow', progress);
            }

            // Update progress text if it exists
            const progressText = goal.querySelector('.progress-text');
            if (progressText) {
                progressText.textContent = `${completedTasks}/${totalTasks} tasks completed`;
            }
        });
    }

    // Helper function to get status class
    function getStatusClass(status) {
        switch (status) {
            case 'completed': return 'success';
            case 'in_progress': return 'warning';
            case 'pending': return 'primary';
            default: return 'secondary';
        }
    }

    // Helper function to get status icon
    function getStatusIcon(status) {
        switch (status) {
            case 'completed': return '<i class="bi bi-check2"></i>';
            case 'in_progress': return '<i class="bi bi-arrow-repeat"></i>';
            case 'pending': return '<i class="bi bi-clock"></i>';
            default: return '';
        }
    }

    // Helper function to show toast messages
    function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    // Helper function to create toast container
    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    // Initialize active status buttons and progress indicators
    document.querySelectorAll('.task-item').forEach(taskItem => {
        const currentStatus = taskItem.getAttribute('data-status') || 'pending';
        const statusButton = taskItem.querySelector(`.task-status-btn[data-status="${currentStatus}"]`);
        if (statusButton) {
            statusButton.classList.remove('btn-outline-' + getStatusClass(currentStatus));
            statusButton.classList.add('btn-' + getStatusClass(currentStatus), 'active');
        }
    });
    updateProgressIndicators();
}); 