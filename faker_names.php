<?php
/**
 * Simple Faker Implementation for Email Generation
 * No external library needed
 */

class SimpleFaker {
    private $firstNames = [
        'john', 'jane', 'michael', 'sarah', 'david', 'emma', 'james', 'olivia',
        'robert', 'sophia', 'william', 'ava', 'richard', 'isabella', 'thomas', 'mia',
        'charles', 'charlotte', 'daniel', 'amelia', 'matthew', 'harper', 'anthony', 'evelyn',
        'mark', 'abigail', 'donald', 'emily', 'steven', 'elizabeth', 'paul', 'sofia',
        'andrew', 'avery', 'joshua', 'ella', 'kenneth', 'scarlett', 'kevin', 'grace',
        'brian', 'chloe', 'george', 'victoria', 'edward', 'madison', 'ronald', 'luna',
        'timothy', 'hannah', 'jason', 'lily', 'jeffrey', 'aria', 'ryan', 'layla',
        'jacob', 'zoe', 'gary', 'penelope', 'nicholas', 'riley', 'eric', 'nora',
        'jonathan', 'hazel', 'stephen', 'ellie', 'larry', 'violet', 'justin', 'aurora',
        'scott', 'savannah', 'brandon', 'audrey', 'benjamin', 'brooklyn', 'samuel', 'bella',
        'raymond', 'claire', 'gregory', 'skylar', 'frank', 'lucy', 'alexander', 'paisley',
        'patrick', 'everly', 'jack', 'anna', 'dennis', 'caroline', 'jerry', 'nova',
        // Indonesian names
        'budi', 'siti', 'ahmad', 'dewi', 'agus', 'rina', 'andi', 'maya',
        'rudi', 'ani', 'hendra', 'lina', 'dedi', 'yuni', 'bambang', 'sri',
        'joko', 'wati', 'tono', 'fitri', 'eko', 'sari', 'doni', 'ratna',
        'adi', 'indah', 'roni', 'ayu', 'yanto', 'dian', 'hadi', 'ika',
    ];
    
    private $lastNames = [
        'smith', 'johnson', 'williams', 'brown', 'jones', 'garcia', 'miller', 'davis',
        'rodriguez', 'martinez', 'hernandez', 'lopez', 'gonzalez', 'wilson', 'anderson', 'thomas',
        'taylor', 'moore', 'jackson', 'martin', 'lee', 'perez', 'thompson', 'white',
        'harris', 'sanchez', 'clark', 'ramirez', 'lewis', 'robinson', 'walker', 'young',
        'allen', 'king', 'wright', 'scott', 'torres', 'nguyen', 'hill', 'flores',
        'green', 'adams', 'nelson', 'baker', 'hall', 'rivera', 'campbell', 'mitchell',
        'carter', 'roberts', 'gomez', 'phillips', 'evans', 'turner', 'diaz', 'parker',
        'cruz', 'edwards', 'collins', 'reyes', 'stewart', 'morris', 'morales', 'murphy',
        'cook', 'rogers', 'gutierrez', 'ortiz', 'morgan', 'cooper', 'peterson', 'bailey',
        'reed', 'kelly', 'howard', 'ramos', 'kim', 'cox', 'ward', 'richardson',
        // Indonesian names
        'santoso', 'wijaya', 'kusuma', 'pratama', 'saputra', 'permana', 'setiawan', 'gunawan',
        'hidayat', 'nugroho', 'wibowo', 'kurniawan', 'susanto', 'lestari', 'utomo', 'putra',
        'suharto', 'raharjo', 'suryanto', 'hartono', 'firmansyah', 'budiman', 'hakim', 'rahman',
    ];
    
    private $adjectives = [
        'cool', 'super', 'mega', 'ultra', 'pro', 'master', 'expert', 'ninja',
        'king', 'queen', 'boss', 'chief', 'prime', 'elite', 'alpha', 'beta',
        'smart', 'fast', 'quick', 'swift', 'rapid', 'turbo', 'hyper', 'max',
        'real', 'true', 'pure', 'fresh', 'new', 'hot', 'top', 'best',
    ];
    
    private $nouns = [
        'user', 'player', 'gamer', 'coder', 'dev', 'admin', 'member', 'client',
        'customer', 'buyer', 'seller', 'trader', 'agent', 'manager', 'leader', 'hero',
        'star', 'rock', 'legend', 'champion', 'winner', 'fighter', 'warrior', 'knight',
    ];
    
    /**
     * Generate random email username
     * @param string $type Type of generation: 'name', 'combo', 'word'
     * @return string
     */
    public function generateUsername($type = 'name') {
        switch ($type) {
            case 'name':
                return $this->generateNameBased();
            case 'combo':
                return $this->generateComboBased();
            case 'word':
                return $this->generateWordBased();
            default:
                return $this->generateNameBased();
        }
    }
    
    /**
     * Generate name-based username (firstname.lastname or firstname_lastname)
     */
    private function generateNameBased() {
        $first = $this->firstNames[array_rand($this->firstNames)];
        $last = $this->lastNames[array_rand($this->lastNames)];
        $separator = rand(0, 1) ? '.' : '_';
        
        // Sometimes add number
        if (rand(0, 2) == 0) {
            $number = rand(1, 999);
            return $first . $separator . $last . $number;
        }
        
        return $first . $separator . $last;
    }
    
    /**
     * Generate combo-based username (adjective + noun + number)
     */
    private function generateComboBased() {
        $adj = $this->adjectives[array_rand($this->adjectives)];
        $noun = $this->nouns[array_rand($this->nouns)];
        $number = rand(1, 9999);
        
        $formats = [
            $adj . $noun . $number,
            $adj . '_' . $noun . $number,
            $adj . '.' . $noun . $number,
            $noun . $number,
        ];
        
        return $formats[array_rand($formats)];
    }
    
    /**
     * Generate word-based username (firstname + number)
     */
    private function generateWordBased() {
        $first = $this->firstNames[array_rand($this->firstNames)];
        $number = rand(100, 9999);
        
        $formats = [
            $first . $number,
            $first . '_' . $number,
            $first . '.' . $number,
        ];
        
        return $formats[array_rand($formats)];
    }
    
    /**
     * Get random first name
     */
    public function getFirstName() {
        return $this->firstNames[array_rand($this->firstNames)];
    }
    
    /**
     * Get random last name
     */
    public function getLastName() {
        return $this->lastNames[array_rand($this->lastNames)];
    }
}
