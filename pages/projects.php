<?php
// Add projects route to index.php allowed pages
$allowed_pages = ['home', 'login', 'register', 'dashboard', 'lessons', 'lesson', 'projects', 'leaderboard', 'profile', 'donate', 'logout'];

// Get user progress for prerequisites
$user_progress = get_user_progress($_SESSION['user_id']);
$completed_lessons = array_filter($user_progress, fn($lesson) => $lesson['completed']);
$completed_count = count($completed_lessons);

// Define projects with prerequisites
$projects = [
    [
        'id' => 1,
        'title' => 'CLI Calculator',
        'description' => 'Build a command-line calculator that can perform basic arithmetic operations',
        'difficulty' => 'beginner',
        'estimated_time' => '2-3 hours',
        'xp_reward' => 500,
        'prerequisites' => 5,
        'skills' => ['Functions', 'Variables', 'User Input', 'Error Handling'],
        'icon' => 'fas fa-calculator',
        'color' => 'green'
    ],
    [
        'id' => 2,
        'title' => 'Todo List Manager',
        'description' => 'Create a CLI todo list application with file persistence',
        'difficulty' => 'beginner',
        'estimated_time' => '3-4 hours',
        'xp_reward' => 750,
        'prerequisites' => 8,
        'skills' => ['Structs', 'Vectors', 'File I/O', 'JSON Serialization'],
        'icon' => 'fas fa-list-check',
        'color' => 'blue'
    ],
    [
        'id' => 3,
        'title' => 'Password Generator',
        'description' => 'Build a secure password generator with customizable options',
        'difficulty' => 'intermediate',
        'estimated_time' => '2-3 hours',
        'xp_reward' => 600,
        'prerequisites' => 10,
        'skills' => ['Random Numbers', 'String Manipulation', 'CLI Arguments'],
        'icon' => 'fas fa-key',
        'color' => 'orange'
    ],
    [
        'id' => 4,
        'title' => 'File Organizer',
        'description' => 'Automatically organize files in directories by type and date',
        'difficulty' => 'intermediate',
        'estimated_time' => '4-5 hours',
        'xp_reward' => 900,
        'prerequisites' => 12,
        'skills' => ['File System', 'Path Manipulation', 'Pattern Matching'],
        'icon' => 'fas fa-folder-tree',
        'color' => 'purple'
    ],
    [
        'id' => 5,
        'title' => 'HTTP Web Server',
        'description' => 'Build a basic HTTP web server that serves static files',
        'difficulty' => 'advanced',
        'estimated_time' => '6-8 hours',
        'xp_reward' => 1200,
        'prerequisites' => 15,
        'skills' => ['TCP Sockets', 'HTTP Protocol', 'Concurrency', 'Error Handling'],
        'icon' => 'fas fa-server',
        'color' => 'red'
    ],
    [
        'id' => 6,
        'title' => 'Chat Application',
        'description' => 'Create a real-time chat application using WebSockets',
        'difficulty' => 'advanced',
        'estimated_time' => '8-10 hours',
        'xp_reward' => 1500,
        'prerequisites' => 18,
        'skills' => ['WebSockets', 'Async Programming', 'Message Broadcasting'],
        'icon' => 'fas fa-comments',
        'color' => 'indigo'
    ]
];

// Check project availability
foreach ($projects as &$project) {
    $project['available'] = $completed_count >= $project['prerequisites'];
    $project['progress'] = min(100, ($completed_count / $project['prerequisites']) * 100);
}
?>

<!-- Projects Header -->
<div class="mb-8">
    <div class="title-large">Rust Projects</div>
    <div class="text-secondary">Build real-world applications and put your Rust skills to the test</div>
</div>

<!-- Progress Overview -->
<div class="content-card mb-8">
    <div class="flex items-center justify-between mb-4">
        <div class="title-medium">Your Project Journey</div>
        <div class="text-orange-400 font-bold"><?= $completed_count ?> lessons completed</div>
    </div>
    
    <div class="grid grid-cols-3 gap-6">
        <?php 
        $available_projects = count(array_filter($projects, fn($p) => $p['available']));
        $total_projects = count($projects);
        ?>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-400"><?= $available_projects ?></div>
            <div class="text-sm text-muted">Available Projects</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-400"><?= $total_projects - $available_projects ?></div>
            <div class="text-sm text-muted">Locked Projects</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-purple-400"><?= array_sum(array_column($projects, 'xp_reward')) ?></div>
            <div class="text-sm text-muted">Total XP Available</div>
        </div>
    </div>
</div>

<!-- Projects Grid -->
<div class="grid lg:grid-cols-2 gap-8">
    <?php foreach ($projects as $project): ?>
        <div class="content-card <?= $project['available'] ? 'hover:border-' . $project['color'] . '-500/50 cursor-pointer' : 'opacity-60' ?> transition-all group">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-<?= $project['color'] ?>-500 rounded-xl flex items-center justify-center <?= !$project['available'] ? 'grayscale' : '' ?>">
                        <i class="<?= $project['icon'] ?> text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold <?= $project['available'] ? 'group-hover:text-' . $project['color'] . '-400' : '' ?> transition-colors">
                            <?= htmlspecialchars($project['title']) ?>
                        </h3>
                        <div class="flex items-center space-x-4 mt-1">
                            <span class="text-xs bg-<?= $project['difficulty'] === 'beginner' ? 'green' : ($project['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-500/20 text-<?= $project['difficulty'] === 'beginner' ? 'green' : ($project['difficulty'] === 'intermediate' ? 'blue' : 'orange') ?>-400 px-2 py-1 rounded-full">
                                <?= ucfirst($project['difficulty']) ?>
                            </span>
                            <span class="text-xs text-muted">
                                <i class="fas fa-clock"></i> <?= $project['estimated_time'] ?>
                            </span>
                            <span class="text-xs text-yellow-400">
                                <i class="fas fa-star"></i> <?= $project['xp_reward'] ?> XP
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (!$project['available']): ?>
                    <div class="text-right">
                        <i class="fas fa-lock text-2xl text-gray-500"></i>
                        <div class="text-xs text-muted mt-1">Locked</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="text-secondary mb-4"><?= htmlspecialchars($project['description']) ?></p>
            
            <!-- Skills -->
            <div class="mb-4">
                <div class="text-sm font-medium text-muted mb-2">Skills you'll practice:</div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($project['skills'] as $skill): ?>
                        <span class="text-xs bg-gray-800 text-gray-300 px-2 py-1 rounded">
                            <?= htmlspecialchars($skill) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Prerequisites -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-muted">Prerequisites:</span>
                    <span class="text-sm <?= $project['available'] ? 'text-green-400' : 'text-orange-400' ?>">
                        <?= $completed_count ?>/<?= $project['prerequisites'] ?> lessons
                    </span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-<?= $project['available'] ? 'green' : 'orange' ?>-500 h-2 rounded-full transition-all" 
                         style="width: <?= $project['progress'] ?>%"></div>
                </div>
            </div>
            
            <!-- Action Button -->
            <div class="flex items-center justify-between">
                <?php if ($project['available']): ?>
                    <button onclick="startProject(<?= $project['id'] ?>)" 
                            class="btn-primary opacity-0 group-hover:opacity-100 transition-all">
                        <i class="fas fa-play mr-2"></i>
                        Start Project
                    </button>
                <?php else: ?>
                    <div class="text-sm text-muted">
                        Complete <?= $project['prerequisites'] - $completed_count ?> more lessons to unlock
                    </div>
                <?php endif; ?>
                
                <button onclick="viewProjectDetails(<?= $project['id'] ?>)" 
                        class="btn-secondary text-sm">
                    <i class="fas fa-info-circle mr-2"></i>
                    Details
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Coming Soon -->
<div class="content-card mt-8" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));">
    <div class="text-center py-8">
        <i class="fas fa-rocket text-4xl text-blue-400 mb-4"></i>
        <div class="title-medium mb-4">More Projects Coming Soon!</div>
        <div class="text-secondary mb-6">
            We're constantly adding new projects to help you master Rust. 
            Suggestions? Let us know what you'd like to build!
        </div>
        <div class="flex justify-center space-x-4">
            <button onclick="openSuggestModal()" class="btn-secondary">
                <i class="fas fa-lightbulb mr-2"></i>
                Suggest a Project
            </button>
            <a href="index.php?page=lessons" class="btn-primary">
                <i class="fas fa-book mr-2"></i>
                Continue Learning
            </a>
        </div>
    </div>
</div>

<!-- Project Modal (placeholder) -->
<div id="project-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="content-card max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="title-medium">Project Details</h2>
            <button onclick="closeProjectModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="project-details">
            <!-- Project details will be loaded here -->
        </div>
    </div>
</div>

<!-- Suggest Project Modal -->
<div id="suggest-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="content-card max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="title-medium">
                <i class="fas fa-lightbulb text-orange-400 mr-3"></i>
                Suggest a Project
            </h2>
            <button onclick="closeSuggestModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="suggest-form" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Project Title *</label>
                <input type="text" id="project-title" required 
                       class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-orange-500 focus:outline-none"
                       placeholder="e.g., Blockchain Explorer, Game Engine, etc.">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Difficulty Level *</label>
                <select id="project-difficulty" required 
                        class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
                    <option value="">Select difficulty...</option>
                    <option value="beginner">Beginner (1-5 lessons completed)</option>
                    <option value="intermediate">Intermediate (6-15 lessons completed)</option>
                    <option value="advanced">Advanced (16+ lessons completed)</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Project Description *</label>
                <textarea id="project-description" required rows="4"
                          class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-orange-500 focus:outline-none resize-none"
                          placeholder="Describe what this project should teach and what the end result would be..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Key Skills/Concepts</label>
                <input type="text" id="project-skills"
                       class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-orange-500 focus:outline-none"
                       placeholder="e.g., Async Programming, WebSockets, File I/O (comma separated)">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Estimated Time</label>
                <select id="project-time"
                        class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-orange-500 focus:outline-none">
                    <option value="">Select estimated time...</option>
                    <option value="1-2 hours">1-2 hours</option>
                    <option value="2-4 hours">2-4 hours</option>
                    <option value="4-6 hours">4-6 hours</option>
                    <option value="6-8 hours">6-8 hours</option>
                    <option value="8+ hours">8+ hours</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Why This Project?</label>
                <textarea id="project-reason" rows="3"
                          class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-orange-500 focus:outline-none resize-none"
                          placeholder="Why would this project be valuable for Rust learners? What real-world problem does it solve?"></textarea>
            </div>
            
            <div class="bg-blue-900/20 border border-blue-500/30 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-blue-400 mt-1"></i>
                    <div class="text-sm text-blue-300">
                        <strong>Pro Tip:</strong> Great project suggestions include real-world applications, 
                        clear learning objectives, and progressive difficulty. We review all suggestions 
                        and the best ones get added to the platform!
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="btn-primary flex-1">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Suggestion
                </button>
                <button type="button" onclick="closeSuggestModal()" class="btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function startProject(projectId) {
    // In a real implementation, this would navigate to the project workspace
    showNotification('Project workspace coming soon! This will open an integrated development environment.', 'info');
}

function viewProjectDetails(projectId) {
    const projects = <?= json_encode($projects) ?>;
    const project = projects.find(p => p.id === projectId);
    
    if (project) {
        document.getElementById('project-details').innerHTML = `
            <div class="space-y-6">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-${project.color}-500 rounded-xl flex items-center justify-center">
                        <i class="${project.icon} text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">${project.title}</h3>
                        <p class="text-secondary">${project.description}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-bold mb-2">Project Info</h4>
                        <ul class="space-y-2 text-sm">
                            <li><strong>Difficulty:</strong> ${project.difficulty}</li>
                            <li><strong>Estimated Time:</strong> ${project.estimated_time}</li>
                            <li><strong>XP Reward:</strong> ${project.xp_reward}</li>
                            <li><strong>Prerequisites:</strong> ${project.prerequisites} lessons</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold mb-2">Skills Practiced</h4>
                        <div class="flex flex-wrap gap-2">
                            ${project.skills.map(skill => `<span class="text-xs bg-gray-800 text-gray-300 px-2 py-1 rounded">${skill}</span>`).join('')}
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800/50 p-4 rounded-lg">
                    <h4 class="font-bold mb-2">What You'll Build</h4>
                    <p class="text-sm text-secondary">
                        This project will guide you through building a complete ${project.title.toLowerCase()} 
                        from scratch. You'll learn industry best practices and write production-quality Rust code.
                    </p>
                </div>
                
                <div class="flex space-x-4">
                    ${project.available ? 
                        `<button onclick="startProject(${project.id})" class="btn-primary flex-1">
                            <i class="fas fa-play mr-2"></i>Start Project
                        </button>` :
                        `<div class="flex-1 text-center py-3 bg-gray-700 rounded-lg text-gray-400">
                            Complete ${project.prerequisites - <?= $completed_count ?>} more lessons to unlock
                        </div>`
                    }
                    <button onclick="closeProjectModal()" class="btn-secondary">Close</button>
                </div>
            </div>
        `;
        
        document.getElementById('project-modal').classList.remove('hidden');
        document.getElementById('project-modal').classList.add('flex');
    }
}

function closeProjectModal() {
    document.getElementById('project-modal').classList.add('hidden');
    document.getElementById('project-modal').classList.remove('flex');
}

function openSuggestModal() {
    document.getElementById('suggest-modal').classList.remove('hidden');
    document.getElementById('suggest-modal').classList.add('flex');
}

function closeSuggestModal() {
    document.getElementById('suggest-modal').classList.add('hidden');
    document.getElementById('suggest-modal').classList.remove('flex');
    document.getElementById('suggest-form').reset();
}

// Close modals on outside click
document.getElementById('project-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProjectModal();
    }
});

document.getElementById('suggest-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuggestModal();
    }
});

// Handle suggest form submission
document.getElementById('suggest-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        title: document.getElementById('project-title').value,
        difficulty: document.getElementById('project-difficulty').value,
        description: document.getElementById('project-description').value,
        skills: document.getElementById('project-skills').value,
        time: document.getElementById('project-time').value,
        reason: document.getElementById('project-reason').value
    };
    
    // Validate required fields
    if (!formData.title || !formData.difficulty || !formData.description) {
        showNotification('Please fill in all required fields!', 'error');
        return;
    }
    
    // Simulate submission (in real app, this would send to backend)
    showNotification('🎉 Project suggestion submitted! We\'ll review it and get back to you. Thanks for helping make Rustnite better!', 'success');
    
    // Log the suggestion (for demo purposes)
    console.log('Project Suggestion:', formData);
    
    closeSuggestModal();
});

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 120px;
        background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        z-index: 1000;
        max-width: 400px;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        line-height: 1.4;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
</script>