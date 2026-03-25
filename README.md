# GLPI Plugin Justificativas de Chamados

Este plugin adiciona importação de justificativas para chamados no GLPI, permitindo:
- importação em massa via CSV/XLSX
- definição de operações (tags) para cada justificativa
- controle de acesso por perfil (direitos READ/UPDATE)
- registro de operador importador em cada linha importada

## Estrutura

- `setup.php`: hooks do plugin, menu e perfil
- `inc/class.justificativas.php`: definição do menu e metadados
- `inc/menu.class.php`: item de menu em Ferramentas
- `inc/profile.class.php`: aba do perfil com direitos do plugin
- `front/index.php`: importação de justificativas e UI principal
- `front/config.php`: cadastro/listagem de operações
- `install/mysql/plugin_justificativas_entries.sql`: esquema de tabelas (tickets/zabbix/telefonia_atendida/telefonia_perdida)

## Requisitos

- GLPI 11.0.0 ou superior
- PHP com extensões padrão (PDO, MbString, etc.)
- Para XLS/XLSX, `phpoffice/phpspreadsheet` opcional (se não instalado só CSV)

## Instalação

1. Copie a pasta para `glpi/plugins/justificativas`.
2. No GLPI, acesse `Configurar > Plugins` e instale/ativar o plugin.
3. Acesse `Configurar > Perfis`, selecione um perfil, e em `Justificativas de Chamados` habilite `Read` e `Write` conforme necessário.

## Uso

### Registrar operações

1. Abra `Ferramentas > Justificativas > Configuração`.
2. Cadastre operações com nome e descrição.

### Importar justificativas

1. Abra `Ferramentas > Justificativas > Importar justificativas`.
2. Escolha o tipo de justificativa (Ticket, Eventos, Telefonia Atendida ou Telefonia Perdida).
3. Selecione operação padrão (opcional) e arquivo CSV/XLSX.
4. CSV esperados: `id` (ticket/evento/telefonia conforme tipo), `closing_date`, `justification`, `operation` (opcional).
5. Execute importação. O plugin informará quantas linhas importadas e puladas.

### Campos gravados (tabelas de justificativas)

- `ticket_id` (em `glpi_plugin_justificativas_tickets`)
- `evento_id` (em `glpi_plugin_justificativas_zabbix`)
- `telefonia_atendida_id` (em `glpi_plugin_justificativas_telefonia_atendida`)
- `telefonia_perdida_id` (em `glpi_plugin_justificativas_telefonia_perdida`)
- `closing_date`
- `justification`
- `operation_id`
- `operation_name`
- `user_id`
- `created_at`, `updated_at`

## Controle de acesso

- Menu aparece apenas para usuário com direito `justificativas` em `READ`.
- Subopção de configuração aparece apenas para `UPDATE`.
- Acessos via `front/index.php` e `front/config.php` são validados.

## Migração

Durante instalação, o plugin garante as tabelas:
- `glpi_plugin_justificativas_operations`
- `glpi_plugin_justificativas_entries` (incluindo `operation_name`)

## Desenvolvimento

- Adicione mais campos conforme necessidade em `front/index.php` e `front/config.php`.
- Também é possível criar página para consulta dos registros `entries` e vínculo direto ao chamado.

## Licença

GPLv2+
