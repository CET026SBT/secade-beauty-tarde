<?php
register_script("components/services", "main");

// 1. Instanciar o Repository ou Service para buscar as categorias reais da BD
require_once __DIR__ . '/../../app/repositories/CategoryRepository.php';

$categoryRepo = new CategoryRepository();
// Função na BD que faz: SELECT id, nome, descricao FROM categoria_profissional
$categoriasBD = $categoryRepo->getAllCategories(); 

// Delays estéticos para a animação CSS (WOW.js)
$delays = ["0.1s", "0.3s", "0.5s"];
$i = 0;
?>

<div class="container-fluid service py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">Os nossos serviços</h1>
            <h1 class="mb-5">Explorando as nossas categorias</h1>
        </div>
        <div class="row g-4 g-md-0 text-center">
            <?php if (!empty($categoriasBD)): ?>
                <?php foreach ($categoriasBD as $cat): ?>
                    <?php 
                        $delay = $delays[$i % count($delays)]; 
                        $i++;
                        // Define o ícone de acordo com a categoria ou usa um ícone padrão
                        $icon = BASE_URL . "/modules/common/img/" . strtolower($cat['nome']) . ".png";
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="service-item h-100 p-4 border-bottom border-end wow fadeIn" data-wow-delay="<?php echo $delay; ?>">
                            <img class="img-fluid mb-3" src="<?php echo htmlspecialchars($icon); ?>" alt="<?php echo htmlspecialchars($cat["nome"]); ?>" onerror="this.src='<?= BASE_URL ?>/modules/common/img/default.png';">
                            <h3 class="mb-3"><?php echo htmlspecialchars($cat["nome"]); ?></h3>
                            <p class="mb-3"><?php echo htmlspecialchars($cat["descricao"]); ?></p>
                            
                            <!-- LINK ATUALIZADO: Passa o ID ou nome da categoria para a nova pagina services.php -->
                            <a class="btn btn-sm btn-primary text-uppercase" href="<?= BASE_URL ?>/services.php?categoria_id=<?= $cat['id'] ?>">
                                Ver Serviços <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhuma categoria disponível no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</div>