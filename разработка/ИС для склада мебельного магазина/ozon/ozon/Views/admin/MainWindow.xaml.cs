using ozon.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Data.Entity;
using Word = Microsoft.Office.Interop.Word;
using System.IO;
using Microsoft.Win32;

namespace ozon.Views
{
    public partial class MainAdminWindow : System.Windows.Window
    {
        private readonly OzonContext _context = new OzonContext();

        public MainAdminWindow()
        {
            InitializeComponent();
        }

        private void OnProductsClick(object sender, RoutedEventArgs e)
        {
            var mainWindow = new WatchProductWindow();
            mainWindow.Show();
        }

        private async System.Threading.Tasks.Task LoadProducts()
        {
            if (_context != null)
            {
                await Dispatcher.BeginInvoke(new Action(() =>
                {
                    this.DataContext = _context.Products.ToList();
                }));
            }
        }

        private void OnAddNewProductClick(object sender, RoutedEventArgs e)
        {
            var addWindow = new AddProductWindow();
            addWindow.Context = _context;
            addWindow.ShowDialog();
            LoadProducts();
        }

        private void Back_Click(object sender, RoutedEventArgs e)
        {
            LoginWindow loginWindow = new LoginWindow();
            loginWindow.Show();
            Close();
        }

        private void OnAddNewOrdersClick(object sender, RoutedEventArgs e)
        {
            var ordersWindow = new AddOrderWindow();
            ordersWindow.ShowDialog();
            LoadProducts();
        }

        private void OnOrdersClick(object sender, RoutedEventArgs e)
        {
            var ordersWindow = new WatchOrdersWindow();
            ordersWindow.ShowDialog();
            LoadProducts();
        }

        // Метод для добавления записи в историю отчетов
        private async Task AddReportToHistoryAsync(string reportName, string filePath, string reportTypeId, int recordCount)
        {
            try
            {
                var fileInfo = new FileInfo(filePath);

                var reportHistory = new ReportHistory
                {
                    ReportName = reportName,
                    ReportTypeId = reportTypeId,
                    FileName = Path.GetFileName(filePath),
                    FilePath = filePath,
                    FileSize = fileInfo.Length
                };

                _context.ReportHistories.Add(reportHistory);
                await _context.SaveChangesAsync();
            }
            catch (Exception ex)
            {
                // Логируем ошибку, но не прерываем процесс экспорта
                System.Diagnostics.Debug.WriteLine($"Ошибка при сохранении истории отчета: {ex.Message}");
            }
        }

        // Метод для экспорта отчета по товарам в Word
        private async void OnExportProductsReportClick(object sender, RoutedEventArgs e)
        {
            try
            {
                // Получаем все товары из базы данных
                var products = _context.Products.ToList();

                if (!products.Any())
                {
                    MessageBox.Show("Нет данных о товарах для экспорта.", "Информация", MessageBoxButton.OK, MessageBoxImage.Information);
                    return;
                }

                // Диалог выбора места сохранения
                var saveFileDialog = new SaveFileDialog
                {
                    Filter = "Word Documents (*.docx)|*.docx",
                    FileName = $"Отчет_по_товарам_{DateTime.Now:yyyyMMdd_HHmmss}.docx",
                    Title = "Сохранить отчет по товарам"
                };

                if (saveFileDialog.ShowDialog() == true)
                {
                    // Создаем приложение Word
                    var wordApp = new Word.Application();
                    wordApp.Visible = false;

                    // Создаем новый документ
                    var document = wordApp.Documents.Add();

                    // Устанавливаем поля документа
                    document.PageSetup.LeftMargin = wordApp.CentimetersToPoints(2);
                    document.PageSetup.RightMargin = wordApp.CentimetersToPoints(2);
                    document.PageSetup.TopMargin = wordApp.CentimetersToPoints(2);
                    document.PageSetup.BottomMargin = wordApp.CentimetersToPoints(2);

                    var paragraph = document.Content.Paragraphs.Add();

                    // Заголовок отчета
                    paragraph.Range.Text = "ОТЧЕТ ПО ТОВАРАМ";
                    paragraph.Range.Font.Size = 16;
                    paragraph.Range.Font.Bold = 1;
                    paragraph.Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                    paragraph.Range.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;
                    paragraph.Range.InsertParagraphAfter();

                    // Дата генерации отчета
                    paragraph.Range.Text = $"Дата формирования: {DateTime.Now:dd.MM.yyyy HH:mm}";
                    paragraph.Range.Font.Size = 12;
                    paragraph.Range.Font.Bold = 0;
                    paragraph.Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphLeft;
                    paragraph.Range.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;
                    paragraph.Range.InsertParagraphAfter();

                    // Пустая строка
                    paragraph.Range.Text = "";
                    paragraph.Range.InsertParagraphAfter();

                    // Создаем таблицу для товаров
                    var tableRange = paragraph.Range;
                    tableRange.Collapse(Word.WdCollapseDirection.wdCollapseEnd);
                    var table = document.Tables.Add(tableRange, products.Count + 1, 6);
                    table.Borders.Enable = 1;
                    table.Range.Font.Size = 12;
                    table.Range.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;

                    // Настраиваем ширину таблицы по ширине страницы
                    table.PreferredWidthType = Word.WdPreferredWidthType.wdPreferredWidthPercent;
                    table.PreferredWidth = 100; // 100% ширины страницы

                    // Заголовки таблицы
                    table.Cell(1, 1).Range.Text = "№";
                    table.Cell(1, 2).Range.Text = "Наименование";
                    table.Cell(1, 3).Range.Text = "Описание";
                    table.Cell(1, 4).Range.Text = "Количество\n(шт)";
                    table.Cell(1, 5).Range.Text = "Цена\n(руб)";
                    table.Cell(1, 6).Range.Text = "Габариты\n(Д×Ш×В, см)";

                    // Устанавливаем ширину столбцов в процентах
                    table.Columns[1].PreferredWidth = 5;   // №
                    table.Columns[2].PreferredWidth = 25;  // Наименование
                    table.Columns[3].PreferredWidth = 35;  // Описание
                    table.Columns[4].PreferredWidth = 10;  // Количество
                    table.Columns[5].PreferredWidth = 10;  // Цена
                    table.Columns[6].PreferredWidth = 15;  // Габариты

                    // Жирный шрифт для заголовков
                    for (int i = 1; i <= 6; i++)
                    {
                        table.Cell(1, i).Range.Font.Bold = 1;
                    }

                    // Заполняем таблицу данными
                    for (int i = 0; i < products.Count; i++)
                    {
                        var product = products[i];
                        table.Cell(i + 2, 1).Range.Text = (i + 1).ToString();
                        table.Cell(i + 2, 2).Range.Text = product.Name ?? "";
                        table.Cell(i + 2, 3).Range.Text = product.Description ?? "";
                        table.Cell(i + 2, 4).Range.Text = product.Quantity.ToString();
                        table.Cell(i + 2, 5).Range.Text = product.Price.ToString("N0");
                        table.Cell(i + 2, 6).Range.Text = $"{product.lehgth}×{product.width}×{product.height}";
                    }

                    // Выравнивание по центру для числовых столбцов
                    for (int i = 1; i <= products.Count + 1; i++)
                    {
                        table.Cell(i, 1).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                        table.Cell(i, 4).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                        table.Cell(i, 5).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                        table.Cell(i, 6).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                    }

                    // Статистика
                    var statsRange = table.Range;
                    statsRange.Collapse(Word.WdCollapseDirection.wdCollapseEnd);
                    statsRange.InsertParagraphAfter();
                    statsRange.Text = "Статистика:";
                    statsRange.Font.Size = 12;
                    statsRange.Font.Bold = 1;
                    statsRange.InsertParagraphAfter();

                    statsRange.Text = $"Всего товаров: {products.Count}";
                    statsRange.Font.Bold = 0;
                    statsRange.InsertParagraphAfter();

                    statsRange.Text = $"Общее количество на складе: {products.Sum(p => p.Quantity):N0} шт";
                    statsRange.InsertParagraphAfter();

                    statsRange.Text = $"Общая стоимость товаров: {products.Sum(p => p.Price * p.Quantity):N0} руб";
                    statsRange.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;

                    // Сохраняем документ
                    document.SaveAs2(saveFileDialog.FileName);
                    document.Close();
                    wordApp.Quit();

                    // Добавляем запись в историю отчетов
                    await AddReportToHistoryAsync(
                        reportName: "Отчет по товарам",
                        filePath: saveFileDialog.FileName,
                        reportTypeId: "1", // ID для отчетов по товарам
                        recordCount: products.Count
                    );

                    MessageBox.Show($"Отчет успешно сохранен:\n{saveFileDialog.FileName}", "Успех", MessageBoxButton.OK, MessageBoxImage.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка при создании отчета: {ex.Message}", "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        // Метод для экспорта отчета по заказам в Word
        private async void OnExportOrdersReportClick(object sender, RoutedEventArgs e)
        {
            try
            {
                // Получаем все заказы из базы данных с информацией о товарах
                var orders = _context.Orders.ToList();
                var products = _context.Products.ToList();

                if (!orders.Any())
                {
                    MessageBox.Show("Нет данных о поставках/отгрузках для экспорта.", "Информация", MessageBoxButton.OK, MessageBoxImage.Information);
                    return;
                }

                // Диалог выбора места сохранения
                var saveFileDialog = new SaveFileDialog
                {
                    Filter = "Word Documents (*.docx)|*.docx",
                    FileName = $"Отчет_по_поставкам_{DateTime.Now:yyyyMMdd_HHmmss}.docx",
                    Title = "Сохранить отчет по поставкам/отгрузкам"
                };

                if (saveFileDialog.ShowDialog() == true)
                {
                    // Создаем приложение Word
                    var wordApp = new Word.Application();
                    wordApp.Visible = false;

                    // Создаем новый документ
                    var document = wordApp.Documents.Add();

                    // Устанавливаем поля документа
                    document.PageSetup.LeftMargin = wordApp.CentimetersToPoints(2);
                    document.PageSetup.RightMargin = wordApp.CentimetersToPoints(2);
                    document.PageSetup.TopMargin = wordApp.CentimetersToPoints(2);
                    document.PageSetup.BottomMargin = wordApp.CentimetersToPoints(2);

                    var paragraph = document.Content.Paragraphs.Add();

                    // Заголовок отчета
                    paragraph.Range.Text = "ОТЧЕТ ПО ПОСТАВКАМ/ОТГРУЗКАМ";
                    paragraph.Range.Font.Size = 16;
                    paragraph.Range.Font.Bold = 1;
                    paragraph.Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                    paragraph.Range.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;
                    paragraph.Range.InsertParagraphAfter();

                    // Дата генерации отчета
                    paragraph.Range.Text = $"Дата формирования: {DateTime.Now:dd.MM.yyyy HH:mm}";
                    paragraph.Range.Font.Size = 12;
                    paragraph.Range.Font.Bold = 0;
                    paragraph.Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphLeft;
                    paragraph.Range.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;
                    paragraph.Range.InsertParagraphAfter();

                    // Пустая строка
                    paragraph.Range.Text = "";
                    paragraph.Range.InsertParagraphAfter();

                    // Создаем таблицу для поставок
                    var tableRange = paragraph.Range;
                    tableRange.Collapse(Word.WdCollapseDirection.wdCollapseEnd);
                    var table = document.Tables.Add(tableRange, orders.Count + 1, 8);
                    table.Borders.Enable = 1;
                    table.Range.Font.Size = 12;
                    table.Range.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;

                    // Настраиваем ширину таблицы по ширине страницы
                    table.PreferredWidthType = Word.WdPreferredWidthType.wdPreferredWidthPercent;
                    table.PreferredWidth = 100; // 100% ширины страницы

                    // Заголовки таблицы
                    table.Cell(1, 1).Range.Text = "№";
                    table.Cell(1, 2).Range.Text = "Товар";
                    table.Cell(1, 3).Range.Text = "Количество\n(шт)";
                    table.Cell(1, 4).Range.Text = "Адрес";
                    table.Cell(1, 5).Range.Text = "Статус";
                    table.Cell(1, 6).Range.Text = "Стоимость\n(руб)";
                    table.Cell(1, 7).Range.Text = "Дата\nсоздания";
                    table.Cell(1, 8).Range.Text = "Локация";

                    // Устанавливаем ширину столбцов в процентах
                    table.Columns[1].PreferredWidth = 5;   // №
                    table.Columns[2].PreferredWidth = 25;  // Товар
                    table.Columns[3].PreferredWidth = 10;  // Количество
                    table.Columns[4].PreferredWidth = 20;  // Адрес
                    table.Columns[5].PreferredWidth = 15;  // Статус
                    table.Columns[6].PreferredWidth = 10;  // Стоимость
                    table.Columns[7].PreferredWidth = 10;  // Дата создания
                    table.Columns[8].PreferredWidth = 15;  // Локация

                    // Жирный шрифт для заголовков
                    for (int i = 1; i <= 8; i++)
                    {
                        table.Cell(1, i).Range.Font.Bold = 1;
                    }

                    // Заполняем таблицу данными
                    for (int i = 0; i < orders.Count; i++)
                    {
                        var order = orders[i];
                        var product = products.FirstOrDefault(p => p.Id == order.ProductId);
                        var productName = product?.Name ?? $"Товар ID: {order.ProductId}";

                        table.Cell(i + 2, 1).Range.Text = (i + 1).ToString();
                        table.Cell(i + 2, 2).Range.Text = productName;
                        table.Cell(i + 2, 3).Range.Text = order.Quantity.ToString();
                        table.Cell(i + 2, 4).Range.Text = order.Address ?? "";
                        table.Cell(i + 2, 5).Range.Text = order.Status ?? "";
                        table.Cell(i + 2, 6).Range.Text = order.Price.ToString("N0");
                        table.Cell(i + 2, 7).Range.Text = order.Create.ToString("dd.MM.yyyy");
                        table.Cell(i + 2, 8).Range.Text = order.location ?? "";
                    }

                    // Выравнивание по центру для числовых столбцов
                    for (int i = 1; i <= orders.Count + 1; i++)
                    {
                        table.Cell(i, 1).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                        table.Cell(i, 3).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                        table.Cell(i, 6).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                        table.Cell(i, 7).Range.ParagraphFormat.Alignment = Word.WdParagraphAlignment.wdAlignParagraphCenter;
                    }

                    // Статистика
                    var statsRange = table.Range;
                    statsRange.Collapse(Word.WdCollapseDirection.wdCollapseEnd);
                    statsRange.InsertParagraphAfter();
                    statsRange.Text = "Статистика:";
                    statsRange.Font.Size = 12;
                    statsRange.Font.Bold = 1;
                    statsRange.InsertParagraphAfter();

                    statsRange.Text = $"Всего поставок/отгрузок: {orders.Count}";
                    statsRange.Font.Bold = 0;
                    statsRange.InsertParagraphAfter();

                    statsRange.Text = $"Общее количество товаров: {orders.Sum(o => o.Quantity):N0} шт";
                    statsRange.InsertParagraphAfter();

                    statsRange.Text = $"Общая стоимость: {orders.Sum(o => o.Price):N0} руб";
                    statsRange.InsertParagraphAfter();

                    // Статистика по статусам
                    statsRange.Text = "\nСтатистика по статусам:";
                    statsRange.Font.Bold = 1;
                    statsRange.InsertParagraphAfter();

                    var statusGroups = orders.GroupBy(o => o.Status)
                        .Select(g => new { Status = g.Key ?? "Без статуса", Count = g.Count() });

                    foreach (var group in statusGroups)
                    {
                        statsRange.Text = $"{group.Status}: {group.Count} операций";
                        statsRange.Font.Bold = 0;
                        statsRange.InsertParagraphAfter();
                    }

                    statsRange.ParagraphFormat.LineSpacingRule = Word.WdLineSpacing.wdLineSpaceSingle;

                    // Сохраняем документ
                    document.SaveAs2(saveFileDialog.FileName);
                    document.Close();
                    wordApp.Quit();

                    // Добавляем запись в историю отчетов
                    await AddReportToHistoryAsync(
                        reportName: "Отчет по поставкам/отгрузкам",
                        filePath: saveFileDialog.FileName,
                        reportTypeId: "2", // ID для отчетов по заказам/поставкам
                        recordCount: orders.Count
                    );

                    MessageBox.Show($"Отчет успешно сохранен:\n{saveFileDialog.FileName}", "Успех", MessageBoxButton.OK, MessageBoxImage.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка при создании отчета: {ex.Message}", "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }
    }
}