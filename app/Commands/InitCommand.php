<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Services\Autoconfig\HorizonAutoconfig;
use App\Services\Autoconfig\LaravelAutoconfig;
use App\Services\Autoconfig\OctaneAutoconfig;
use App\Services\Autoconfig\Resolver;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use stdClass;

class InitCommand extends Command
{
    use InteractsWithMaidApi;

    private static array $adjectives = array("able", "above", "absolute", "accepted", "accurate", "ace", "active", "actual", "adapted", "adapting", "adequate", "adjusted", "advanced", "alert", "alive", "allowed", "allowing", "amazed", "amazing", "ample", "amused", "amusing", "apparent", "apt", "arriving", "artistic", "assured", "assuring", "awaited", "awake", "aware", "balanced", "becoming", "beloved", "better", "big", "blessed", "bold", "boss", "brave", "brief", "bright", "bursting", "busy", "calm", "capable", "capital", "careful", "caring", "casual", "causal", "central", "certain", "champion", "charmed", "charming", "cheerful", "chief", "choice", "civil", "classic", "clean", "clear", "clever", "climbing", "close", "closing", "coherent", "comic", "communal", "complete", "composed", "concise", "concrete", "content", "cool", "correct", "cosmic", "crack", "creative", "credible", "crisp", "crucial", "cuddly", "cunning", "curious", "current", "cute", "daring", "darling", "dashing", "dear", "decent", "deciding", "deep", "definite", "delicate", "desired", "destined", "devoted", "direct", "discrete", "distinct", "diverse", "divine", "dominant", "driven", "driving", "dynamic", "eager", "easy", "electric", "elegant", "emerging", "eminent", "enabled", "enabling", "endless", "engaged", "engaging", "enhanced", "enjoyed", "enormous", "enough", "epic", "equal", "equipped", "eternal", "ethical", "evident", "evolved", "evolving", "exact", "excited", "exciting", "exotic", "expert", "factual", "fair", "faithful", "famous", "fancy", "fast", "feasible", "fine", "finer", "firm", "first", "fit", "fitting", "fleet", "flexible", "flowing", "fluent", "flying", "fond", "frank", "free", "fresh", "full", "fun", "funny", "game", "generous", "gentle", "genuine", "giving", "glad", "glorious", "glowing", "golden", "good", "gorgeous", "grand", "grateful", "great", "growing", "grown", "guided", "guiding", "handy", "happy", "hardy", "harmless", "healthy", "helped", "helpful", "helping", "heroic", "hip", "holy", "honest", "hopeful", "hot", "huge", "humane", "humble", "humorous", "ideal", "immense", "immortal", "immune", "improved", "in", "included", "infinite", "informed", "innocent", "inspired", "integral", "intense", "intent", "internal", "intimate", "inviting", "joint", "just", "keen", "key", "kind", "knowing", "known", "large", "lasting", "leading", "learning", "legal", "legible", "lenient", "liberal", "light", "liked", "literate", "live", "living", "logical", "loved", "loving", "loyal", "lucky", "magical", "magnetic", "main", "major", "many", "massive", "master", "mature", "maximum", "measured", "meet", "merry", "mighty", "mint", "model", "modern", "modest", "moral", "more", "moved", "moving", "musical", "mutual", "national", "native", "natural", "nearby", "neat", "needed", "neutral", "new", "next", "nice", "noble", "normal", "notable", "noted", "novel", "obliging", "on", "one", "open", "optimal", "optimum", "organic", "oriented", "outgoing", "patient", "peaceful", "perfect", "pet", "picked", "pleasant", "pleased", "pleasing", "poetic", "polished", "polite", "popular", "positive", "possible", "powerful", "precious", "precise", "premium", "prepared", "present", "pretty", "primary", "prime", "pro", "probable", "profound", "promoted", "prompt", "proper", "proud", "proven", "pumped", "pure", "quality", "quick", "quiet", "rapid", "rare", "rational", "ready", "real", "refined", "regular", "related", "relative", "relaxed", "relaxing", "relevant", "relieved", "renewed", "renewing", "resolved", "rested", "rich", "right", "robust", "romantic", "ruling", "sacred", "safe", "saved", "saving", "secure", "select", "selected", "sensible", "set", "settled", "settling", "sharing", "sharp", "shining", "simple", "sincere", "singular", "skilled", "smart", "smashing", "smiling", "smooth", "social", "solid", "sought", "sound", "special", "splendid", "square", "stable", "star", "steady", "sterling", "still", "stirred", "stirring", "striking", "strong", "stunning", "subtle", "suitable", "suited", "summary", "sunny", "super", "superb", "supreme", "sure", "sweeping", "sweet", "talented", "teaching", "tender", "thankful", "thorough", "tidy", "tight", "together", "tolerant", "top", "topical", "tops", "touched", "touching", "tough", "true", "trusted", "trusting", "trusty", "ultimate", "unbiased", "uncommon", "unified", "unique", "united", "up", "upright", "upward", "usable", "useful", "valid", "valued", "vast", "verified", "viable", "vital", "vocal", "wanted", "warm", "wealthy", "welcome", "welcomed", "well", "whole", "willing", "winning", "wired", "wise", "witty", "wondrous", "workable", "working", "worthy");
    private static array $names = array("ox", "ant", "ape", "asp", "bat", "bee", "boa", "bug", "cat", "cod", "cow", "cub", "doe", "dog", "eel", "eft", "elf", "elk", "emu", "ewe", "fly", "fox", "gar", "gnu", "hen", "hog", "imp", "jay", "kid", "kit", "koi", "lab", "man", "owl", "pig", "pug", "pup", "ram", "rat", "ray", "yak", "bass", "bear", "bird", "boar", "buck", "bull", "calf", "chow", "clam", "colt", "crab", "crow", "dane", "deer", "dodo", "dory", "dove", "drum", "duck", "fawn", "fish", "flea", "foal", "fowl", "frog", "gnat", "goat", "grub", "gull", "hare", "hawk", "ibex", "joey", "kite", "kiwi", "lamb", "lark", "lion", "loon", "lynx", "mako", "mink", "mite", "mole", "moth", "mule", "mutt", "newt", "orca", "oryx", "pika", "pony", "puma", "seal", "shad", "slug", "sole", "stag", "stud", "swan", "tahr", "teal", "tick", "toad", "tuna", "wasp", "wolf", "worm", "wren", "yeti", "adder", "akita", "alien", "aphid", "bison", "boxer", "bream", "bunny", "burro", "camel", "chimp", "civet", "cobra", "coral", "corgi", "crane", "dingo", "drake", "eagle", "egret", "filly", "finch", "gator", "gecko", "ghost", "ghoul", "goose", "guppy", "heron", "hippo", "horse", "hound", "husky", "hyena", "koala", "krill", "leech", "lemur", "liger", "llama", "louse", "macaw", "midge", "molly", "moose", "moray", "mouse", "panda", "perch", "prawn", "quail", "racer", "raven", "rhino", "robin", "satyr", "shark", "sheep", "shrew", "skink", "skunk", "sloth", "snail", "snake", "snipe", "squid", "stork", "swift", "swine", "tapir", "tetra", "tiger", "troll", "trout", "viper", "wahoo", "whale", "zebra", "alpaca", "amoeba", "baboon", "badger", "beagle", "bedbug", "beetle", "bengal", "bobcat", "caiman", "cattle", "cicada", "collie", "condor", "cougar", "coyote", "dassie", "donkey", "dragon", "earwig", "falcon", "feline", "ferret", "gannet", "gibbon", "glider", "goblin", "gopher", "grouse", "guinea", "hermit", "hornet", "iguana", "impala", "insect", "jackal", "jaguar", "jennet", "kitten", "kodiak", "lizard", "locust", "maggot", "magpie", "mammal", "mantis", "marlin", "marmot", "marten", "martin", "mayfly", "minnow", "monkey", "mullet", "muskox", "ocelot", "oriole", "osprey", "oyster", "parrot", "pigeon", "piglet", "poodle", "possum", "python", "quagga", "rabbit", "raptor", "rodent", "roughy", "salmon", "sawfly", "serval", "shiner", "shrimp", "spider", "sponge", "tarpon", "thrush", "tomcat", "toucan", "turkey", "turtle", "urchin", "vervet", "walrus", "weasel", "weevil", "wombat", "anchovy", "anemone", "bluejay", "buffalo", "bulldog", "buzzard", "caribou", "catfish", "chamois", "cheetah", "chicken", "chigger", "cowbird", "crappie", "crawdad", "cricket", "dogfish", "dolphin", "firefly", "garfish", "gazelle", "gelding", "giraffe", "gobbler", "gorilla", "goshawk", "grackle", "griffon", "grizzly", "grouper", "gryphon", "haddock", "hagfish", "halibut", "hamster", "herring", "jackass", "javelin", "jawfish", "jaybird", "katydid", "ladybug", "lamprey", "lemming", "leopard", "lioness", "lobster", "macaque", "mallard", "mammoth", "manatee", "mastiff", "meerkat", "mollusk", "monarch", "mongrel", "monitor", "monster", "mudfish", "muskrat", "mustang", "narwhal", "oarfish", "octopus", "opossum", "ostrich", "panther", "peacock", "pegasus", "pelican", "penguin", "phoenix", "piranha", "polecat", "primate", "quetzal", "raccoon", "rattler", "redbird", "redfish", "reptile", "rooster", "sawfish", "sculpin", "seagull", "skylark", "snapper", "spaniel", "sparrow", "sunbeam", "sunbird", "sunfish", "tadpole", "termite", "terrier", "unicorn", "vulture", "wallaby", "walleye", "warthog", "whippet", "wildcat", "aardvark", "airedale", "albacore", "anteater", "antelope", "arachnid", "barnacle", "basilisk", "blowfish", "bluebird", "bluegill", "bonefish", "bullfrog", "cardinal", "chipmunk", "cockatoo", "crawfish", "crayfish", "dinosaur", "doberman", "duckling", "elephant", "escargot", "flamingo", "flounder", "foxhound", "glowworm", "goldfish", "grubworm", "hedgehog", "honeybee", "hookworm", "humpback", "kangaroo", "killdeer", "kingfish", "labrador", "lacewing", "ladybird", "lionfish", "longhorn", "mackerel", "malamute", "marmoset", "mastodon", "moccasin", "mongoose", "monkfish", "mosquito", "pangolin", "parakeet", "pheasant", "pipefish", "platypus", "polliwog", "porpoise", "reindeer", "ringtail", "sailfish", "scorpion", "seahorse", "seasnail", "sheepdog", "shepherd", "silkworm", "squirrel", "stallion", "starfish", "starling", "stingray", "stinkbug", "sturgeon", "terrapin", "titmouse", "tortoise", "treefrog", "werewolf", "woodcock");

    protected $signature = 'init';

    protected $description = 'Initialize a new project in the current directory';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $this->warn(file_get_contents(base_path('resources/views/banner.txt')));
        $this->warn('Welcome to the maid initialization wizard! I will now guide you through the setup,');
        $this->warn('for most cases I have already defined some common parameters. This process takes');
        $this->warn('no more than one minute.');
        $this->newLine();
        $this->warn(sprintf('You are currently using the following version: %s', App::version()));
        $this->newLine();
        $this->warn('Discord: https://ghostzero.dev/discord');
        $this->warn('GitHub: https://github.com/maidtools/maid');
        $this->warn('Issues: https://github.com/maidtools/maid/issues');

        $this->newLine();
        $this->warn('Before we start, please select your Kubernetes cluster...');

        try {
            $clusters = $this->getClustersAnticipate($maid);
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        $this->newLine();
        $this->info(' Which cluster do you want to use?');
        foreach ($clusters as $id => $cluster) {
            $this->info(sprintf(' <fg=white>> [%s] %s</> (%s)', $id, $cluster->name, $cluster->engine));
        }

        $this->newLine();
        $this->warn('Visit <fg=white>https://maid.sh/self-hosted</> to create your own cluster.');
        $this->newLine();

        do {
            $clusterId = $this->anticipate('Please enter the cluster id', $clusters->keys()->toArray());
        } while (!in_array($clusterId, $clusters->keys()->toArray()));

        $this->warn('Now I need some information about your deployment...');

        $randomName = $this->generateName();

        $deploymentName = $this->ask('How would you like to name the deployment?', 'app');
        $projectName = $this->ask('How would you like to name the namespace?', $randomName);
        $environment = $this->ask('How would you like to name the environment?', 'production');

        try {
            $result = $maid
                ->withUserAccessToken()
                ->createProject([
                    'kubernetes_cluster_id' => $clusterId,
                    'name' => $projectName,
                ]);
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if ($result->success()) {
            $projectId = $result->data()->id;
            $this->warn(sprintf('Your new project id is: %s', $projectId));
        } else {
            $this->error('Unable to generate new project id.');

            return self::FAILURE;
        }

        $this->warn('Now it\'s time for me to get to know your app better....');

        $environmentDefinition = $this->findPackages([
            new LaravelAutoconfig(),
            new HorizonAutoconfig(),
            new OctaneAutoconfig(),
        ]);

        if (empty($environmentDefinition)) {
            $this->newLine();
        }

        $this->warn('Wonderful you have made it!');
        $this->newLine();
        $this->warn(sprintf('To deploy your application use \'maid deploy %s\'.', $environment));

        ksort($environmentDefinition);

        Manifest::save([
            'name' => $deploymentName,
            'project' => $projectId,
            'environments' => [
                $environment => $environmentDefinition
            ]
        ]);

        return self::SUCCESS;
    }

    private function generateName(): string
    {
        return sprintf(
            '%s-%s-%s',
            self::$adjectives[mt_rand(0, count(self::$names) - 1)],
            self::$names[mt_rand(0, count(self::$names) - 1)],
            strtolower(Str::random(6))
        );
    }

    private function findPackages(array $packages): array
    {
        $filename = sprintf('%s/composer.json', getcwd());

        if (!file_exists($filename)) {
            return [];
        }

        $composer = json_decode(file_get_contents($filename), true);

        if (empty($composer['require'])) {
            return [];
        }

        return Collection::make($packages)
            ->filter(fn(Resolver $resolver) => array_key_exists($resolver->getPackage(), $composer['require']))
            ->map(fn(Resolver $resolver) => $resolver->getEnvironmentConfig($this))
            ->collapse()
            ->toArray();
    }

    /**
     * @throws RequestRequiresClientIdException
     * @throws LoginRequiredException
     * @throws GuzzleException
     */
    private function getClustersAnticipate(Maid $maid): Collection
    {
        $result = $maid
            ->withUserAccessToken()
            ->getClusters();

        return Collection::make($result->data())
            ->mapWithKeys(function (stdClass $cluster) {
                return [$cluster->id => $cluster];
            });
    }
}
