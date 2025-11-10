<?php
/**
 * Simple Faker Class for Generating Realistic Email Usernames
 * Generates realistic-looking email addresses without external dependencies
 */

class SimpleFaker {
    
    private $firstNames = [
        'john', 'james', 'robert', 'michael', 'william', 'david', 'richard', 'joseph', 'thomas', 'charles',
        'mary', 'patricia', 'jennifer', 'linda', 'elizabeth', 'barbara', 'susan', 'jessica', 'sarah', 'karen',
        'daniel', 'matthew', 'anthony', 'mark', 'donald', 'steven', 'paul', 'andrew', 'joshua', 'kenneth',
        'emily', 'ashley', 'amanda', 'melissa', 'deborah', 'stephanie', 'rebecca', 'laura', 'sharon', 'cynthia',
        'christopher', 'brian', 'kevin', 'george', 'edward', 'ronald', 'timothy', 'jason', 'jeffrey', 'ryan',
        'nancy', 'betty', 'sandra', 'margaret', 'dorothy', 'lisa', 'michelle', 'kimberly', 'angela', 'helen'
    ];
    
    private $lastNames = [
        'smith', 'johnson', 'williams', 'brown', 'jones', 'garcia', 'miller', 'davis', 'rodriguez', 'martinez',
        'hernandez', 'lopez', 'gonzalez', 'wilson', 'anderson', 'thomas', 'taylor', 'moore', 'jackson', 'martin',
        'lee', 'perez', 'thompson', 'white', 'harris', 'sanchez', 'clark', 'ramirez', 'lewis', 'robinson',
        'walker', 'young', 'allen', 'king', 'wright', 'scott', 'torres', 'nguyen', 'hill', 'flores',
        'green', 'adams', 'nelson', 'baker', 'hall', 'rivera', 'campbell', 'mitchell', 'carter', 'roberts'
    ];
    
    private $adjectives = [
        'cool', 'super', 'mega', 'ultra', 'pro', 'epic', 'awesome', 'great', 'best', 'top',
        'smart', 'fast', 'quick', 'bright', 'happy', 'lucky', 'sunny', 'blue', 'red', 'green',
        'dark', 'light', 'wild', 'free', 'bold', 'brave', 'true', 'real', 'pure', 'fresh'
    ];
    
    private $nouns = [
        'user', 'gamer', 'player', 'master', 'king', 'queen', 'hero', 'star', 'ninja', 'dragon',
        'tiger', 'wolf', 'eagle', 'lion', 'bear', 'fox', 'hawk', 'shark', 'panther', 'falcon',
        'warrior', 'knight', 'wizard', 'hunter', 'ranger', 'fighter', 'racer', 'rider', 'pilot', 'captain'
    ];
    
    private $words = [
        'alpha', 'beta', 'gamma', 'delta', 'omega', 'sigma', 'theta', 'phoenix', 'nexus', 'apex',
        'vortex', 'matrix', 'cyber', 'digital', 'tech', 'byte', 'pixel', 'code', 'data', 'cloud',
        'storm', 'thunder', 'lightning', 'fire', 'ice', 'wind', 'earth', 'water', 'shadow', 'light'
    ];
    
    /**
     * Generate a random username based on type
     * 
     * @param string $type Type of username: 'name', 'combo', or 'word'
     * @return string Generated username
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
     * Generate name-based username (e.g., john.smith, sarah_jones123)
     */
    private function generateNameBased() {
        $firstName = $this->randomElement($this->firstNames);
        $lastName = $this->randomElement($this->lastNames);
        
        $formats = [
            '%s.%s',           // john.smith
            '%s_%s',           // john_smith
            '%s%s',            // johnsmith
            '%s.%s%d',         // john.smith123
            '%s_%s%d',         // john_smith456
            '%s%s%d',          // johnsmith789
            '%s.%s%d%d',       // john.smith12
            '%s%d',            // john123
        ];
        
        $format = $this->randomElement($formats);
        $number = rand(1, 999);
        $number2 = rand(10, 99);
        
        return sprintf($format, $firstName, $lastName, $number, $number2);
    }
    
    /**
     * Generate combo-based username (e.g., cooluser123, super_gamer456)
     */
    private function generateComboBased() {
        $adjective = $this->randomElement($this->adjectives);
        $noun = $this->randomElement($this->nouns);
        
        $formats = [
            '%s%s%d',          // cooluser123
            '%s_%s%d',         // cool_user456
            '%s%s',            // cooluser
            '%s_%s',           // cool_user
            '%s%d%s',          // cool123user
        ];
        
        $format = $this->randomElement($formats);
        $number = rand(1, 999);
        
        return sprintf($format, $adjective, $noun, $number);
    }
    
    /**
     * Generate word-based username (e.g., alpha123, phoenix456)
     */
    private function generateWordBased() {
        $firstName = $this->randomElement($this->firstNames);
        $word = $this->randomElement($this->words);
        
        $formats = [
            '%s%d',            // john1234
            '%s_%d',           // john_1234
            '%s%s',            // johnalpha
            '%s_%s',           // john_alpha
            '%s%d%s',          // john123alpha
            '%s%s%d',          // johnalpha456
        ];
        
        $format = $this->randomElement($formats);
        $number = rand(100, 9999);
        
        // Randomly choose between firstName or word
        $base = rand(0, 1) ? $firstName : $word;
        $second = rand(0, 1) ? $word : '';
        
        return sprintf($format, $base, $number, $second);
    }
    
    /**
     * Get random element from array
     */
    private function randomElement($array) {
        return $array[array_rand($array)];
    }
}
