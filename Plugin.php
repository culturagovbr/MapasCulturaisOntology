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
    var $propertiesPrefix = 'http://vocab.cultura.gov.br/term/';
    
    /** Dicionário para mapear atributos das entidades para os atributos da ontologia
     * Do lado esquerdo temos a propriedade conforme ela aparece na estrutura de dados do Mapas Culturais
     * Do lado direito temos o nome da propriedade na ontologia.
     */
    var $propertiesMap = [
        'name' => 'nome',
        'dataDeNascimento' => 'data-de-nascimento',
        'genero' => 'genero',
        'emailPublico' => 'email',
        'telefonePublico' => 'telefone',
        'site' => 'website',
        'facebook' => 'perfil-facebook',
        'twitter' => 'perfil-twitter',
        'googleplus' => 'perfil-google',
        'En_Municipio' => 'municipio',
        'En_Estado' => 'uf',
        
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
                // ex: http://vocab.cultura.gov.br/Agente
                $metaTags[] = [ 'typeof' => $plugin->classPrefix . $plugin->entityTypeMap[$entityClassType] ];
                
                // Pegamos a lista de propriedades da entidade
                $entityProperties = $entity->getPropertiesMetadata();
                
                // Iteramos por todas as propriedades da entidade
                // Se houver um mapeamento de uma propriedade da entidade no mapas para uma propriedade da ontologia, adicionamos mais uma meta tag
                foreach ($entityProperties as $propertyName => $propertyDefinition) {
                    
                    // Se existe um mapeamento com o nome desta propriedade e .... se a entidade tem um valor para esta propriedade
                    if (array_key_exists($propertyName, $plugin->propertiesMap) && !is_null($entity->{$propertyName})) {
                    
                        
                        
                        // Adicionamos mais uma meta tag para ser impressa
                        // O nome da propriedade é o prefixo da URI da Ontologia seguido do nome da propriedade 
                        // ex: http://vocab.cultura.gov.br/term/nome
                        $metaTags[] = [ 
                            'property' => $plugin->propertiesPrefix . $plugin->propertiesMap[$propertyName],
                            'content' => $entity->{$propertyName} 
                        ];
                    
                    }
                
                }

                /////// RELAÇÕES 
                
                // Relações do Agente
                if ($entityClassType == 'Agent') {
                
                    // Agente realiza Ação
                    foreach ($entity->events as $event) {
                    
                        $metaTags[] = [ 
                            'property' => $plugin->propertiesPrefix . 'organizaAcao',
                            'content' => $event->getSingleUrl()
                        ];
                    
                    }
                    foreach ($entity->projects as $project) {
                    
                        $metaTags[] = [ 
                            'property' => $plugin->propertiesPrefix . 'organizaAcao',
                            'content' => $project->getSingleUrl()
                        ];
                    
                    }
                    
                    // Agente mantem espaço
                    foreach ($entity->spaces as $space) {
                    
                        $metaTags[] = [ 
                            'property' => $plugin->propertiesPrefix . 'mantemEspaco',
                            'content' => $space->getSingleUrl()
                        ];
                    
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
     * Recebe um array com as meta tags que serão impressas. 
     * 
     * Cada item do array é um array de chaves e valores que são os atributos e valores da meta tag.
     * 
     * 
     * 
     * @param array $metaTags
     *
     */ 
    public function printMetaTags($metaTags) {
        
        if (!is_array($metaTags))
            return;
       
        foreach ($metaTags as $metaTag) {
        
            if (!is_array($metaTag))
                continue;
            
            $meta = "<meta ";
            
            foreach ($metaTag as $key => $value) {
            
                    $meta .= "$key=\"{$value}\" " ;
            
            }
            
            $meta .= "/>\n ";
            
            echo $meta;
            
        }
        
    }

    public function register() {
        
    }
    
}
