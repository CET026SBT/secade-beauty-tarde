<?php
$teamMembers = [
    [
        'name' => 'Lily Taylor',
        'role' => 'Cabeleireiro',
        'image' => 'img/team-1.jpg',
        'delay' => '0.1s',
        'social' => ['facebook' => 'https://www.facebook.com', 'instagram' => '#', 'linkedin' => '#']
    ],
    [
        'name' => 'Olivia Smith',
        'role' => 'Manicura',
        'image' => 'img/team-2.jpg',
        'delay' => '0.3s',
        'social' => ['facebook' => '#', 'instagram' => '#', 'linkedin' => '#']
    ],
    [
        'name' => 'Ava Brown',
        'role' => 'Especialista em tendências',
        'image' => 'img/team-3.jpg',
        'delay' => '0.5s',
        'social' => ['facebook' => '#', 'instagram' => '#', 'linkedin' => '#']
    ],
    [
        'name' => 'Amelia Jones',
        'role' => 'SPA Especialista',
        'image' => 'img/team-4.jpg',
        'delay' => '0.7s',
        'social' => ['facebook' => '#', 'instagram' => '#', 'linkedin' => '#']
    ]
];
?>

<div class="container-fluid overflow-hidden py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.2s">
            <h1 class="font-dancing-script text-primary">Membros da equipa</h1>
            <h1 class="mb-5">Os nossos especialistas</h1>
        </div>
        <div class="row g-4 team">
            <?php foreach ($teamMembers as $member): ?>
                <div class="col-md-6 col-lg-3 wow fadeIn" data-wow-delay="<?php echo $member['delay']; ?>">
                    <div class="team-item position-relative overflow-hidden">
                        <img class="img-fluid w-100" src="<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                        <div class="team-overlay">
                            <p class="text-primary mb-1"><?php echo htmlspecialchars($member['role']); ?></p>
                            <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                            <div class="d-flex justify-content-center">
                                <?php if (!empty($member['social']['facebook'])): ?>
                                    <a class="btn btn-dark btn-sm-square border-2 me-3" href="<?php echo htmlspecialchars($member['social']['facebook']); ?>">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($member['social']['instagram'])): ?>
                                    <a class="btn btn-dark btn-sm-square border-2 me-3" href="<?php echo htmlspecialchars($member['social']['instagram']); ?>">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($member['social']['linkedin'])): ?>
                                    <a class="btn btn-dark btn-sm-square border-2" href="<?php echo htmlspecialchars($member['social']['linkedin']); ?>">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
