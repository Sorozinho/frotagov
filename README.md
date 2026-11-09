# FrotaGov

Sistema web para controle de **abastecimento e manutenção da frota municipal**, desenvolvido como Projeto Final de Programação II — Ciência da Computação, UNOESC Videira.

**Integrante:** Lucas Alves Scortegagna (427420)
**Professor:** Leandro Otavio Cordova Vieira

## Objetivo

Centralizar o registro de abastecimentos e ordens de manutenção dos veículos e máquinas das secretarias municipais, calculando consumo médio, alertando revisões próximas e mostrando o custo por veículo e por secretaria.

## Funcionalidades

- CRUD de **Secretarias**
- CRUD de **Veículos** (automóveis em km e máquinas pesadas em horas)
- CRUD de **Abastecimentos** (atualiza o hodômetro/horímetro do veículo automaticamente)
- CRUD de **Ordens de manutenção** com itens (peças e serviços)
- Consumo médio por veículo (km/L para automóvel, L/h para máquina)
- Alerta de revisão próxima (10.000 km ou 250 h)
- Relatório de custos por secretaria e por veículo em um período

## Tecnologias

- PHP 8.1+ (orientado a objetos, sem framework)
- MySQL 8 / MariaDB com PDO
- HTML + CSS
- Git / GitHub
- Mermaid (diagramas UML e ER)

## Conceitos de POO aplicados

| Conceito | Onde está |
|---|---|
| Encapsulamento | Todos os atributos `private`, com setters que validam (`Automovel::setPlaca`, `Abastecimento::setLitros`...) |
| Herança | `Automovel` e `MaquinaPesada` estendem `Veiculo`; repositórios estendem `RepositorioPdo` |
| Polimorfismo | `calcularConsumo()`, `intervaloRevisao()`, `getUnidadeMedidor()` dão resultados diferentes para cada tipo de veículo |
| Abstração | Classes abstratas `Veiculo` e `RepositorioPdo` |
| Interface | `App\Contracts\Repositorio` (salvar, buscarPorId, listarTodos, excluir) |
| Trait | `App\Traits\Auditavel` (datas de criação/alteração) |
| Associação | `Abastecimento` → `Veiculo`; `OrdemManutencao` → `Veiculo` |
| Agregação | `Secretaria` ◇— `Veiculo` |
| Composição | `OrdemManutencao` ◆— `ItemServico` |

Diagramas completos em [`docs/diagramas.md`](docs/diagramas.md).

## Segurança e integridade

- Consultas com **prepared statements** (PDO) — proteção contra SQL Injection
- `htmlspecialchars` em toda saída — proteção contra XSS
- Validação de dados nas classes de modelo (exceções `InvalidArgumentException`)
- **Transações**: ordem + itens e abastecimento + hodômetro são gravados juntos ou nada é gravado
- Chaves estrangeiras com `ON DELETE RESTRICT` (não apaga veículo com histórico) e `ON DELETE CASCADE` (itens morrem com a ordem)
- Senha do banco fora do repositório (`config/config.php` no `.gitignore`)

## Estrutura

```
frotagov/
├── bootstrap.php          # autoload, sessão e funções auxiliares
├── config/
│   └── config.example.php # modelo da configuração do banco
├── database/
│   ├── schema.sql         # cria o banco e as tabelas
│   └── seed.sql           # dados fictícios para teste
├── docs/
│   └── diagramas.md       # UML de classes e modelo ER
├── public/                # páginas acessadas pelo navegador
│   ├── index.php, secretarias.php, veiculos.php,
│   ├── abastecimentos.php, ordens.php, relatorio.php
│   └── css/estilo.css
├── src/
│   ├── Contracts/Repositorio.php
│   ├── Database/Conexao.php
│   ├── Exceptions/RegistroVinculadoException.php
│   ├── Model/             # Secretaria, Veiculo, Automovel, MaquinaPesada,
│   │                      # Abastecimento, OrdemManutencao, ItemServico
│   ├── Repository/        # acesso ao banco com PDO
│   └── Traits/Auditavel.php
└── templates/             # topo e rodapé das páginas
```

## Como instalar e executar (Windows + XAMPP)

1. Instale o [XAMPP](https://www.apachefriends.org/) e ligue **Apache** e **MySQL** no painel.
2. Copie a pasta `frotagov` para `C:\xampp\htdocs\`.
3. Abra http://localhost/phpmyadmin → aba **SQL** → cole o conteúdo de `database/schema.sql` e execute. Depois faça o mesmo com `database/seed.sql`.
4. Copie `config/config.example.php` para `config/config.php` (no XAMPP a senha do root é vazia, não precisa mudar nada).
5. Acesse http://localhost/frotagov/public

**Alternativa sem Apache** (servidor embutido do PHP):

```bash
C:\xampp\php\php.exe -S localhost:8000 -t public
```

e acesse http://localhost:8000

## Limitações e melhorias futuras

- Sem login/controle de usuários
- Sem proteção CSRF nos formulários
- Sem integração com sistemas da Prefeitura ou com notas fiscais
- Número fixo de linhas de itens na ordem de manutenção
