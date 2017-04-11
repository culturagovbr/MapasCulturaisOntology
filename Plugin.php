<?php

/**
 * Plugin que adiciona meta tags que relacionam os indivíduos do Mapas Culturais a Ontologia da Gestão Cultural
 * 
 * Em construção
 * 
 * Referências:
 * 
 * Baseado em exemplos da documentação da RDFa
 * 
 * https://www.w3.org/TR/rdfa-core/
 * 
 * https://www.w3.org/TR/rdfa-lite/
 * 
 * https://www.w3.org/MarkUp/2009/rdfa-for-html-authors
 * 
 * 
 */
 
  
namespace MapasCulturaisOntology;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin {
    
    // Dicionário para mapear os tipos de entidade do Mapas Culturais para as classes da Ontologia
    var $entityTypeMap = [
        'Space' => 'Espaço',
        'Agent' => 'Agente',
        'Event' => 'Ação',
        'Project' => 'Ação',
        
        // Subclasses de agente
        'Individual' => 'Agente/Agente-Individual',
        'Coletivo' => 'Agente/Agente-Coletivo',
    ];
    
    // Prefixo da URI das classes da Ontologia
    var $classPrefix = 'http://vocab.cultura.gov.br/';
    
    // Prefixo da URI das propriedades da Ontologia
    var $propertiesPrefix = 'http://vocab.cultura.gov.br/';
    
    // Dicionário para mapear atributos das entidades para os atributos da ontologia
    var $propertiesMap = [
        'name' => 'nome',
        
    ];

    public function _init() {
        $app = App::i();
        
        $plugin = $this;
        
        // Adicionamos um callback para inserir conteúdo dentro da tag HEAD do HTML
        $app->hook('mapasculturais.head', function() use ($app, $plugin) {
            
            
            // Se estamos em um perfil de alguma entidade
            if (!is_null($app->view->controller->requestedEntity)) {
                
                // A entidade que estamos visualizando
                $entity = $app->view->controller->requestedEntity;
                
                // Iniciamos a lista vazia de meta tags que serão impressas
                $metaTags = [];
                
                // Pegamos o tipo de entidade (Agent, Space, Event, Project)
                $entityClassType = $entity->getEntityType();
                
                // Se for agente, checamos a subclasse
                if ($entityClassType == 'Agent') {
                    $AgentType = $entity->type;
                    if (is_array($AgentType) && array_key_exists('name', $AgentType)) {
                        
                        // Caso haja um valor do tipo de agente, usamos ele para mapear diretamente para uma subclasse da ontologia
                        $entityClassType = $AgentType['name']; // Individual ou coletivo
                    }
                }
                
                
                // Definimos a primeira meta tag chamada "typeof" que indica de qual classe da ontologia é este indivíduo
                // O valor desta propriedade é o prefixo da URI da Ontologia seguido do nome da classe
                $metaTags['typeof'] = $plugin->classPrefix . $plugin->entityTypeMap[$entityClassType];
                
                // Pegamos a lista de propriedades da entidade
                $entityProperties = $entity->getPropertiesMetadata();
                
                // Iteramos por todas as propriedades da entidade
                // Se houver um mapeamento de uma propriedade da entidade no mapas para uma propriedade da ontologia, adicionamos mais uma meta tag
                foreach ($entityProperties as $propertyName => $propertyDefinition) {
                
                    // Se existe um mapeamento com o nome desta propriedade e .... se a entidade tem um valor para esta propriedade
                    if (array_key_exists($propertyName, $plugin->propertiesMap) && !is_null($entity->{$propertyName})) {
                    
                        // Adicionamos mais uma meta tag para ser impressa
                        // O nome da propriedade é o prefixo da URI da Ontologia seguido do nome da propriedade
                        $metaTags[$plugin->propertiesPrefix . $plugin->propertiesMap[$propertyName]] = $entity->{$propertyName};
                    
                    }
                    
                
                }

                // Imprime as meta tags
                $plugin->printMetaTags($metaTags);
                
            }
            
        }, 1000);
        
        
    }
    
    /**
     * Imprime as meta tags com as informações da entidade que estão relacionadas a ontolgia
     *
     * Recebe um array com as meta tags que serão impressas. Espera que haja uma meta tag referente a classe da entidade, chamada typeof, 
     * e uma série de meta tags que são as propriedades.
     * 
     * A chave de cada item da array é o nome da propriedade e o valor é o valor.
     * 
     * Com exceção do item do array com a chave "typeof", todos os itens serão impressos no formato:
     * 
     * <meta property="$propertyName" content="$propoertyValue" />
     * 
     * O typeof será impresso no formato: 
     * 
     * <meta typeof="$propoertyValue" />
     * 
     * @param array $metaTags
     *
     */ 
    public function printMetaTags($metaTags) {
        
        if (!is_array($metaTags))
            return;
       
        foreach ($metaTags as $key => $value) {
        
            if ($key == 'typeof') {
            
                echo "<meta $key=\"{$value}\" />\n";
                
            } else {
                
                echo "<meta property=\"{$key}\" content=\"{$value}\" />\n";
            
            }
        
        }
        
    }

    public function register() {
        
    }
    
}
