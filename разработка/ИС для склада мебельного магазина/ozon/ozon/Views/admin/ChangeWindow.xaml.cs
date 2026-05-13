using ozon.Models;
using System;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Media;

namespace ozon.Views
{
    public partial class ChangeWindow : Window
    {
        private readonly int productId;
        private Product _product;

        public ChangeWindow(Product product)
        {
            this.productId = product.Id;
            this._product = product;
            this.DataContext = product;

            InitializeComponent();
            SetTextQualityOptions();
        }

        private void SetTextQualityOptions()
        {
            TextOptions.SetTextFormattingMode(this, TextFormattingMode.Display);
            TextOptions.SetTextRenderingMode(this, TextRenderingMode.ClearType);
        }

        private void SaveButtonClick(object sender, RoutedEventArgs e)
        {
            using (var db = new OzonContext())
            {
                try
                {
                    // Валидация обязательных полей
                    if (string.IsNullOrWhiteSpace(BoxName.Text))
                    {
                        MessageBox.Show("Заполните наименование товара", "Ошибка",
                            MessageBoxButton.OK, MessageBoxImage.Error);
                        return;
                    }

                    if (!int.TryParse(BoxPrice.Text, out int price) || price < 0)
                    {
                        MessageBox.Show("Введите корректную цену", "Ошибка",
                            MessageBoxButton.OK, MessageBoxImage.Error);
                        return;
                    }

                    if (!int.TryParse(BoxQuantity.Text, out int quantity) || quantity < 0)
                    {
                        MessageBox.Show("Введите корректное количество", "Ошибка",
                            MessageBoxButton.OK, MessageBoxImage.Error);
                        return;
                    }

                    // Парсим габариты (необязательные поля)
                    int length = int.TryParse(BoxLength.Text, out int l) ? l : 0;
                    int width = int.TryParse(BoxWidth.Text, out int w) ? w : 0;
                    int height = int.TryParse(BoxHeight.Text, out int h) ? h : 0;

                    // Ищем нужный продукт в базе данных
                    var updatedProduct = db.Products.FirstOrDefault(p => p.Id == _product.Id);
                    if (updatedProduct != null)
                    {
                        updatedProduct.Quantity = quantity;
                        updatedProduct.Name = BoxName.Text.Trim();
                        updatedProduct.Description = BoxDescription.Text?.Trim();
                        updatedProduct.ImgUrl = BoxImgUrl.Text?.Trim();
                        updatedProduct.Price = price;
                        updatedProduct.lehgth = length;
                        updatedProduct.width = width;
                        updatedProduct.height = height;

                        db.SaveChanges();
                        MessageBox.Show("✅ Товар успешно обновлен", "Успех",
                            MessageBoxButton.OK, MessageBoxImage.Information);
                        Close();
                    }
                    else
                    {
                        MessageBox.Show("Ошибка обновления товара! Товар не найден.", "Ошибка",
                            MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Ошибка при сохранении: {ex.Message}", "Ошибка",
                        MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }

        private void DeleteButtonClick(object sender, RoutedEventArgs e)
        {
            var result = MessageBox.Show("Вы уверены, что хотите удалить этот товар?",
                "Подтверждение удаления",
                MessageBoxButton.YesNo,
                MessageBoxImage.Question);

            if (result != MessageBoxResult.Yes)
                return;

            using (var db = new OzonContext())
            {
                Product productToDelete = db.Products.FirstOrDefault(o => o.Id == productId);
                Order order = db.Orders.FirstOrDefault(o => o.ProductId == productId);

                try
                {
                    if (productToDelete != null)
                    {
                        if (order == null)
                        {
                            db.Products.Remove(productToDelete);
                            db.SaveChanges();

                            MessageBox.Show("✅ Товар успешно удален", "Успех",
                                MessageBoxButton.OK, MessageBoxImage.Information);
                            Close();
                        }
                        else
                        {
                            MessageBox.Show("❌ Товар нельзя удалить, так как он есть в заказах",
                                "Ошибка", MessageBoxButton.OK, MessageBoxImage.Warning);
                        }
                    }
                    else
                    {
                        MessageBox.Show("❌ Товар с указанным ID не найден", "Ошибка",
                            MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"❌ Ошибка при удалении: {ex.Message}", "Ошибка",
                        MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }
    }
}