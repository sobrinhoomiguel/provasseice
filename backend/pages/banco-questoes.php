-- pages/banco-questoes.php (Página principal integrada)
<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Incluir configurações
require_once '../backend/config/database.php';
require_once '../backend/classes/Question.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $question = new Question($db);
    
    // Buscar estatísticas para exibir no painel
    $stats = $question->getStats();
    $statsData = $stats['success'] ? $stats['data'] : [];
    
} catch (Exception $e) {
    error_log("Error loading questions page: " . $e->getMessage());
    $statsData = [];
}

$pageTitle = "Banco de Questões";
$currentPage = "banco-questoes";
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEICE - <?php echo $pageTitle; ?></title>
    <!-- Seus includes de CSS aqui -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Seus estilos CSS aqui - copiados do HTML anterior */
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                <a href="/inicio">Início</a>
                <span>/</span>
                <span><?php echo $pageTitle; ?></span>
            </div>
            <div class="user-info">
                <div>
                    <div class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                    <div class="system-subtitle">Sistema de Ensino Integrado de Campos Elíseos</div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <i class="fas fa-database"></i>
                    <div>
                        <h1><?php echo $pageTitle; ?></h1>
                        <p>Gerencie e organize questões para suas avaliações</p>
                        <?php if (!empty($statsData['total'])): ?>
                            <small><?php echo $statsData['total']; ?> questão(ões) cadastrada(s)</small>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="primary-btn" onclick="openCreateQuestionModal()">
                    <i class="fas fa-plus"></i>
                    Nova Questão
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="controls-section">
                <form id="searchForm" onsubmit="searchQuestions(event)">
                    <div class="search-container">
                        <div class="form-group">
                            <label for="searchTerm">Buscar Questão</label>
                            <input type="text" id="searchTerm" name="searchTerm" class="form-control" 
                                   placeholder="Digite palavras-chave da questão...">
                        </div>
                        <div class="form-group">
                            <label for="subject">Disciplina</label>
                            <select id="subject" name="subject" class="form-control">
                                <option value="">Todas as disciplinas</option>
                                <option value="matematica">Matemática</option>
                                <option value="portugues">Português</option>
                                <option value="ciencias">Ciências</option>
                                <option value="historia">História</option>
                                <option value="geografia">Geografia</option>
                                <option value="ingles">Inglês</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="difficulty">Dificuldade</label>
                            <select id="difficulty" name="difficulty" class="form-control">
                                <option value="">Todas as dificuldades</option>
                                <option value="facil">Fácil</option>
                                <option value="medio">Médio</option>
                                <option value="dificil">Difícil</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="grade">Série</label>
                            <select id="grade" name="grade" class="form-control">
                                <option value="">Todas as séries</option>
                                <option value="6ano">6º Ano</option>
                                <option value="7ano">7º Ano</option>
                                <option value="8ano">8º Ano</option>
                                <option value="9ano">9º Ano</option>
                                <option value="1medio">1º Médio</option>
                                <option value="2medio">2º Médio</option>
                                <option value="3medio">3º Médio</option>
                            </select>
                        </div>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                        <button type="button" class="clear-btn" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                            Limpar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Questions Table (Hidden when empty) -->
            <div class="questions-table" id="questionsTable" style="display: none;">
                <!-- Conteúdo da tabela será carregado via JavaScript -->
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="emptyState">
                <div class="empty-icon">
                    <i class="fas fa-database"></i>
                </div>
                <h3 class="empty-title">Nenhuma questão encontrada</h3>
                <p class="empty-subtitle">
                    <?php if (empty($statsData['total']) || $statsData['total'] == 0): ?>
                        Você ainda não possui questões cadastradas no sistema.<br>
                        Comece criando sua primeira questão para montar suas avaliações.
                    <?php else: ?>
                        Nenhuma questão encontrada com os filtros aplicados.<br>
                        Tente ajustar os termos de busca ou criar uma nova questão.
                    <?php endif; ?>
                </p>
                <div class="empty-actions">
                    <button class="primary-btn" onclick="openCreateQuestionModal()">
                        <i class="fas fa-plus"></i>
                        <?php echo (empty($statsData['total']) || $statsData['total'] == 0) ? 'Criar Primeira Questão' : 'Nova Questão'; ?>
                    </button>
                    <button class="secondary-btn" onclick="openImportModal()">
                        <i class="fas fa-upload"></i>
                        Importar Questões
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <?php include '../includes/question-modals.php'; ?>

    <script>
        // Configuração da API
        const API_BASE_URL = '/pages/api/questions';
        
        // Dados iniciais do PHP
        const initialStats = <?php echo json_encode($statsData); ?>;
        
        // JavaScript do banco de questões (mesmo código do HTML anterior)
        // ... (incluir todo o JavaScript do artifact anterior)
        
        // Atualizar URLs das APIs para corresponder à estrutura do backend
        async function loadQuestions() {
            try {
                const emptyState = document.getElementById('emptyState');
                const questionsTable = document.getElementById('questionsTable');
                
                emptyState.style.display = 'none';
                questionsTable.style.display = 'none';
                
                // Usar a API real
                const response = await fetch(`${API_BASE_URL}/list.php`);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao carregar questões');
                }
                
                questionsData = data.data?.questions || [];
                
                if (questionsData.length === 0) {
                    showEmptyState();
                } else {
                    showQuestionsTable(questionsData);
                }
                
            } catch (error) {
                console.error('Error loading questions:', error);
                showError('Erro ao carregar questões: ' + error.message);
                showEmptyState();
            }
        }
        
        // Atualizar função de busca
        async function searchQuestions(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const filters = Object.fromEntries(formData.entries());
            
            document.getElementById('questionsTable').classList.add('loading');
            
            try {
                const response = await fetch(`${API_BASE_URL}/search.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(filters)
                });
                
                const data = await response.json();
                
                document.getElementById('questionsTable').classList.remove('loading');
                
                if (!response.ok) {
                    throw new Error(data.message || 'Erro na busca');
                }
                
                if (data.data.questions.length === 0) {
                    showEmptySearchResults();
                } else {
                    showQuestionsTable(data.data.questions);
                }
                
            } catch (error) {
                console.error('Error searching questions:', error);
                document.getElementById('questionsTable').classList.remove('loading');
                showError('Erro ao buscar questões: ' + error.message);
            }
        }
        
        // Resto do JavaScript permanece igual...
    </script>
</body>
</html>